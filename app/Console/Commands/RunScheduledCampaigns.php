<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RunScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:run';
    protected $description = 'Run WhatsApp campaigns that are scheduled and due';

    public function handle()
    {
        $now = now();
        $this->info('RunScheduledCampaigns called at: ' . $now);

        $campaigns = Campaign::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->get();

        $this->info('Found scheduled campaigns: ' . $campaigns->count());

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns to run (DB query returned 0).');
            return Command::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Running campaign #{$campaign->id}: {$campaign->name}");
            $campaign->update(['status' => 'running']);

            try {
                $this->sendCampaign($campaign);
                $campaign->update(['status' => 'completed']);
                $this->info("Campaign #{$campaign->id} completed");
            } catch (\Throwable $e) {
                $campaign->update(['status' => 'failed']);
                $this->error("Failed campaign {$campaign->id}: " . $e->getMessage());

                Log::error("Failed campaign {$campaign->id}", [
                    'campaign_id' => $campaign->id,
                    'exception'   => $e,
                ]);
            }
        }

        return Command::SUCCESS;
    }

    protected function sendCampaign(Campaign $campaign)
    {
        // 1) Build numbers list
        if ($campaign->whatsapp_numbers === 'ALL') {
            $contacts = Contact::where('user_id', $campaign->user_id)->get();
            $numbers = $contacts->pluck('phone')->filter()->unique()->values()->all();
        } else {
            $numbers = collect(explode(',', $campaign->whatsapp_numbers))
                ->map(fn ($n) => trim($n))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (empty($numbers)) {
            $this->info("Campaign #{$campaign->id} has no numbers to send to.");
            return;
        }

        // 2) Get WhatsApp credentials for this user
        $settings = Setting::where('user_id', $campaign->user_id)->first();

        if (!$settings || !$settings->phone_number_id || !$settings->access_token) {
            throw new \RuntimeException('WhatsApp settings missing for user ' . $campaign->user_id);
        }

        $phoneNumberId = $settings->phone_number_id;
        $accessToken   = $settings->access_token;
        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

        // 3) Send to each number
        foreach ($numbers as $to) {
            $this->info("Sending to {$to} for campaign #{$campaign->id}");

            $response = Http::withToken($accessToken)->post($url, [
                "messaging_product" => "whatsapp",
                "to"   => $to,
                "type" => "template",
                "template" => [
                    "name"     => $campaign->template_name,
                    "language" => ["code" => "en"],
                ],
            ]);

            if (!$response->successful()) {
                $this->error("WhatsApp API failed for {$to}: " . $response->body());

                Log::warning('WhatsApp API failed for campaign', [
                    'campaign_id' => $campaign->id,
                    'to'          => $to,
                    'body'        => $response->body(),
                ]);
            } else {
                $this->info("Message sent successfully to {$to}");
            }
        }
    }
}

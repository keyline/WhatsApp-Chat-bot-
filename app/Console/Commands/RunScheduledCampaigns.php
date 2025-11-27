<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Carbon\Carbon;

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
        //  dd($campaigns); die;
        $this->info('Found scheduled campaigns: ' . $campaigns->count());

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns to run (DB query returned 0).');
            return Command::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Running campaign #{$campaign->id}: {$campaign->name}");
            $campaign->update(['status' => 'running']);

            try {
                $sentCount = $this->sendCampaign($campaign);
                $campaign->increment('total_sent', $sentCount);

            if ($campaign->schedule_type === 'daily') {
                $next = $campaign->next_run_at
                    ? Carbon::parse($campaign->next_run_at)->addDay()
                    : now()->addDay();

                $campaign->update([
                    'status'      => 'scheduled',
                    'next_run_at' => $next,
                ]);
            } else {
                $campaign->update([
                    'status'      => 'completed',
                    'next_run_at' => null,
                ]);
            }

            } catch (\Throwable $e) {
                $campaign->update(['status' => 'failed']);

                Log::error("Failed campaign {$campaign->id}", [
                    'campaign_id' => $campaign->id,
                    'exception'   => $e,
                ]);
            }
        }

        return Command::SUCCESS;
    }

    public function sendCampaign(Campaign $campaign): int
    {
        $userId = $campaign->user_id;

        // 1) Load settings
        $settings = Setting::where('user_id', $userId)->firstOrFail();

        $accessToken = $settings->access_token;
        $phoneNumberId = $settings->phone_number_id;

        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

        // 2) Build $numbers array
        $contacts = Contact::whereIn('id', $campaign->contact_ids ?? [])->get(); // adjust to your structure

        $numbers = $contacts
            ->pluck('phone')        // column name in your contacts table
            ->filter()              // remove null
            ->unique()
            ->values()
            ->all();

        $sentCount = 0;

        foreach ($numbers as $to) {
            $this->info("Sending to {$to} for campaign #{$campaign->id}");

            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'     => $campaign->template_name,
                    'language' => ['code' => $campaign->template_language ?? 'en'],
                    // add components if needed
                ],
            ];

            $response = Http::withToken($accessToken)->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('WhatsApp API failed for campaign', [
                    'campaign_id' => $campaign->id,
                    'to'          => $to,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);

                $campaign->increment('total_failed');
                $this->error("WhatsApp API failed for {$to}: " . $response->body());
                continue;
            }

            $sentCount++;
            $this->info("Message sent successfully to {$to}");
        }

        return $sentCount;
    }
}

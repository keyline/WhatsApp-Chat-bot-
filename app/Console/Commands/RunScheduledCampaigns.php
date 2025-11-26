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

        $campaigns = Campaign::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
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
                $sentCount = $this->sendCampaign($campaign);

                // update stats
                $campaign->increment('total_sent', $sentCount);

                if ($campaign->schedule_type === 'daily') {
                    // schedule next run for tomorrow, keep status scheduled
                    $next = $campaign->next_run_at
                        ? $campaign->next_run_at->copy()->addDay()
                        : now()->addDay();

                    $campaign->update([
                        'status'     => 'scheduled',
                        'next_run_at'=> $next,
                    ]);
                    $this->info("Campaign #{$campaign->id} rescheduled for daily run at {$next}");
                } else {
                    // now / once: complete after running
                    $campaign->update([
                        'status'     => 'completed',
                        'next_run_at'=> null,
                    ]);
                    $this->info("Campaign #{$campaign->id} completed");
                }

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

    protected function sendCampaign(Campaign $campaign): int
    {
        // ... (same as yours, build $numbers)

        $sentCount = 0;

        foreach ($numbers as $to) {
            $this->info("Sending to {$to} for campaign #{$campaign->id}");

            $response = Http::withToken($accessToken)->post($url, [ /* ... */ ]);

            if (!$response->successful()) {
                // log fail
                Log::warning('WhatsApp API failed for campaign', [
                    'campaign_id' => $campaign->id,
                    'to'          => $to,
                    'body'        => $response->body(),
                ]);

                $campaign->increment('total_failed');
                $this->error("WhatsApp API failed for {$to}: " . $response->body());
            } else {
                $sentCount++;
                $this->info("Message sent successfully to {$to}");
            }
        }

        return $sentCount;
    }

}

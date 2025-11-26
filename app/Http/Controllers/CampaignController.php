<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Setting;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Http;

class CampaignController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $user = Auth::user();

        $settings = Setting::where('user_id', $user->id)->first();

        if (!$settings || !$settings->business_account_id || !$settings->access_token) {
            return redirect()
                ->route('templates') 
                ->with('error', 'Please configure WhatsApp API credentials in Settings first.');
        }

        $wabaId      = $settings->business_account_id;
        $accessToken = $settings->access_token;

        $response = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v20.0/{$wabaId}/message_templates", [
                'limit' => 200,
            ]);

        if (!$response->ok()) {
            return redirect()
                ->route('templates')
                ->with('error', 'Failed to fetch templates from Meta.');
        }

        $meta_templates = $response->json('data') ?? [];

        // load campaigns with messages count
        $campaigns = Campaign::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
//    dd($campaigns); die;
        $contacts = Contact::where('user_id', Auth::id())
        ->orderBy('name')
        ->get();    

        return view('campaigns.index', ['campaigns' => $campaigns, 'meta_templates' => $meta_templates, 'contacts' => $contacts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'template_name' => 'required|string|max:255',
            'audience_type' => 'required|in:all,selected',
            'selected_numbers'   => 'nullable|array',
            'selected_numbers.*' => 'string',
            'type'          => 'required|in:broadcast,automation,bot',
            'schedule_type' => 'required|in:now,once,daily',
            'scheduled_at'  => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Build recipient list
        if ($validated['audience_type'] === 'all') {
            $whatsappNumbers = 'ALL';
        } else {
            $numbers = $validated['selected_numbers'] ?? [];
            if (empty($numbers)) {
                return back()->withErrors(['selected_numbers' => 'Please select at least one contact.']);
            }
            $whatsappNumbers = implode(',', $numbers);
        }

        $now = now();
        $scheduleType = $validated['schedule_type'];

        // set next_run_at for scheduled campaigns
        if ($scheduleType === 'now') {
            $nextRunAt = null; // no schedule
        } else {
            $nextRunAt = $validated['scheduled_at']
                ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $validated['scheduled_at'])
                : $now;
        }

        // Create campaign
        $campaign = Campaign::create([
            'user_id'          => Auth::id(),
            'name'             => $validated['name'],
            'template_name'    => $validated['template_name'],
            'whatsapp_numbers' => $whatsappNumbers,
            'audience_type'    => $validated['audience_type'],
            'type'             => $validated['type'],
            'status'           => ($scheduleType === 'now') ? 'running' : 'scheduled',
            'schedule_type'    => $scheduleType,
            'scheduled_at'     => $validated['scheduled_at'] ?? null,
            'next_run_at'      => $nextRunAt,
        ]);

        // If schedule_type = now → send immediately
        if ($scheduleType === 'now') {
            $this->sendNow($campaign);
        }

        return redirect()
            ->route('campaigns')
            ->with('success', 'Campaign created successfully.');
    }


    protected function sendNow(Campaign $campaign)
    {
        // Build numbers list
        if ($campaign->whatsapp_numbers === 'ALL') {
            $contacts = \App\Models\Contact::where('user_id', $campaign->user_id)->get();
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
            return;
        }

        // Get settings
        $settings = \App\Models\Setting::where('user_id', $campaign->user_id)->first();

        if (!$settings || !$settings->phone_number_id || !$settings->access_token) {
            return;
        }

        $phoneNumberId = $settings->phone_number_id;
        $accessToken   = $settings->access_token;

        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

        $sentCount = 0;

        foreach ($numbers as $to) {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)->post($url, [
                "messaging_product" => "whatsapp",
                "to"   => $to,
                "type" => "template",
                "template" => [
                    "name"     => $campaign->template_name,
                    "language" => ["code" => "en"],
                ],
            ]);

            if ($response->successful()) {
                $sentCount++;
            }
        }

        // Update DB
        $campaign->update([
            'total_sent' => $sentCount,
            'status'     => 'completed',
            'next_run_at' => null,
        ]);
    }

}

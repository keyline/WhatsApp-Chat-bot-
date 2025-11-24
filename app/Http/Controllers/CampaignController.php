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
        $campaigns = Campaign::withCount('targets')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

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
            'scheduled_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Build whatsapp_numbers based on audience_type
        if ($validated['audience_type'] === 'all') {
            // special marker to indicate "send to all contacts"
            $whatsappNumbers = 'ALL';
        } else {
            // audience_type = selected
            $numbers = $validated['selected_numbers'] ?? [];

            if (empty($numbers)) {
                return back()
                    ->withInput()
                    ->withErrors(['selected_numbers' => 'Please select at least one contact.']);
            }

            // convert array ["+9111...", "+9222..."] → "+9111...,+9222..."
            $whatsappNumbers = implode(',', $numbers);
        }

        Campaign::create([
            'user_id'          => Auth::id(),
            'name'             => $validated['name'],
            'template_name'    => $validated['template_name'],
            'whatsapp_numbers' => $whatsappNumbers,
            'type'             => $validated['type'],
            'status'           => 'scheduled',   // or 'draft'
            'scheduled_at'     => $validated['scheduled_at'] ?? null,
        ]);

        return redirect()
            ->route('campaigns')
            ->with('success', 'Campaign created successfully.');
    }

}

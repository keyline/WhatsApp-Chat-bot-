<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Template;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class MessageController extends Controller
{
    public function index()
    {
        // Load all messages for current user with status history
        $messages = Message::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('messages.index', compact('messages'));
    }

    public function create()
    {
        // Templates to select from
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

        //   dd($meta_templates); die;
        return view('messages.create', ['meta_templates' => $meta_templates]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone'        => 'required|string',
            'template_name'  => 'required|string',
        ]);

        // TODO: call WhatsApp API here with selected template + phone
                $user = Auth::user();

        $settings = Setting::where('user_id', $user->id)->first();

        if (!$settings || !$settings->business_account_id || !$settings->access_token) {
            return redirect()
                ->route('templates') 
                ->with('error', 'Please configure WhatsApp API credentials in Settings first.');
        }

        $wabaId      = $settings->business_account_id;
        $accessToken = $settings->access_token;
        $phoneNumberId = $settings->phone_number_id;
        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

         $response = Http::withToken($accessToken)->post($url, [
                "messaging_product" => "whatsapp",
                "to"   => $data['phone'],
                "type" => "template",
                "template" => [
                    "name" => $data['template_name'],
                    "language" => [ "code" => "en" ],
                    // add components if template has variables
                ],
            ]);

            if (!$response->ok()){
                return redirect()->back()
                     ->with('error', 'Failed to send message via WhatsApp API.');
            }

            // 👉 API success – parse response
            $body = $response->json();

            $waMessageId    = Arr::get($body, 'messages.0.id');              // "wamid..."
            $initialStatus  = Arr::get($body, 'messages.0.message_status');  // "accepted"

            // Save in DB as history
            Message::create([
                'user_id'       => auth()->id(),
                'phone'         => $data['phone'],
                'template_name' => $data['template_name'],
                'wa_message_id' => $waMessageId,
                'status'        => $initialStatus ?? 'accepted',
                'direction'     => 'OUT',
                'raw_payload'   => json_encode($body),
            ]);

        //    echo $data['phone'] . "<br>" . $data['template_name']. "<br>" . $waMessageId. "<br>" . $initialStatus; die;
            return redirect()
                ->route('messages.index')
                ->with('success', 'Message sent (status: ' . ($initialStatus ?? 'accepted') . ').');
                }
}

<?php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use App\Models\Setting;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BotController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->id();

        $settings = Setting::firstOrCreate(
            ['user_id' => $userId],
            ['verify_token' => Str::random(32)]
        );

        $conversations = Conversation::where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate(20);

        return view('bot_flows.bot-settings', [
            'settings'      => $settings,
            'conversations' => $conversations,
        ]);
    }



   public function sendManualMessage(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        // Get current user's WhatsApp API settings
        $settings = Setting::where('user_id', Auth::id())->firstOrFail();

        $phoneNumberId = $settings->phone_number_id;
        $accessToken   = $settings->access_token;
        // $settings->business_account_id is not needed for sending messages

        // Ensure phone has "+" prefixed
        $rawPhone = $request->input('phone');
        $cleanPhone = ltrim($rawPhone, '+'); // remove any existing +
        $to = '+' . $cleanPhone;            // add "+"

        // Call WhatsApp Cloud API
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => [
                    'body' => $request->input('message'),
                ],
            ]);

        if (! $response->successful()) {
            // You can log this if needed
            // logger()->error('WhatsApp send error', ['body' => $response->body()]);

            return back()->with('error', 'Failed to send message: ' . $response->body());
        }

        return back()->with('success', 'Message sent to ' . $to);
    }
}

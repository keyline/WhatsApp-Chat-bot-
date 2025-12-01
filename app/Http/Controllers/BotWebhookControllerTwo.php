<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\BotQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Secret used ONLY for webhook verification
        $myVerifyToken = config('services.whatsapp.webhook_verify_token');

        /**
         * 1) META WEBHOOK VERIFICATION (GET)
         */
        if ($request->isMethod('get')) {
            $mode      = $request->query('hub_mode');
            $sentToken = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $sentToken === $myVerifyToken) {
                return response($challenge, 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response('Invalid verify token', 403);
        }

        /**
         * 2) INCOMING WHATSAPP MESSAGE (POST)
         */
        $payload = $request->all();
        Log::info('WA POST payload', $payload);

        // Which phone number received this message?
        $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');

        if (! $phoneNumberId) {
            return response()->json(['status' => 'no-phone-number-id']);
        }

        // Find the settings row for that phone number
        $settings = Setting::where('phone_number_id', $phoneNumberId)->first();

        if (! $settings) {
            Log::warning('No Setting found for phone_number_id', ['phone_number_id' => $phoneNumberId]);
            return response()->json(['status' => 'no-settings']);
        }

        // Extract first message
        $message = data_get($payload, 'entry.0.changes.0.value.messages.0');

        if (! $message) {
            return response()->json(['status' => 'ignored']);
        }

        $from = $message['from'] ?? null;
        $text = data_get($message, 'text.body', '');

        if (! $from) {
            return response()->json(['status' => 'no-from']);
        }

        // 3) Find or create conversation for this user + phone
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $settings->user_id,
                'phone'   => $from,
            ],
            [
                'step' => 'start',
                'data' => [],
                'phone' => $from,
            ]
        );

        // 4) Decide reply based on conversation state (DYNAMIC)
        $reply = $this->getReplyForMessage($conversation, trim($text));

        // 4b) Store outgoing reply in JSON history
        $data = $conversation->data ?? [];
        $data['history'][] = [
            'direction' => 'out',
            'text'      => $reply,
            'step'      => $conversation->step,
            'time'      => now()->toIso8601String(),
        ];
        $conversation->data = $data;
        $conversation->save();

        // 5) Send reply via WhatsApp Cloud API
        $this->sendWhatsAppText($settings, $from, $reply);

        return response()->json(['status' => 'ok']);
    }

    /**
     * MAIN CHATBOT FLOW (now dynamic from DB)
     */
    protected function getReplyForMessage(Conversation $conv, string $text): string
    {
        $data = $conv->data ?? [];

        // Log incoming message into history
        $data['history'][] = [
            'direction' => 'in',
            'text'      => $text,
            'step'      => $conv->step,
            'time'      => now()->toIso8601String(),
        ];
        $conv->data = $data;
        $conv->save();

        $normalizedText = strtolower(trim($text));

        // If flow already completed, polite reply
        if ($conv->step === 'completed') {
            // try to find by the raw text (user might send number to re-open)
            $digits = preg_replace('/\D+/', '', $text);
            $user = null;
            if ($digits) {
                $user = ConversationUser::where('phone1', 'like', "%{$digits}%")
                    ->orWhere('phone2', 'like', "%{$digits}%")
                    ->first();
            }
            $name = $user->name ?? ($conv->data['name'] ?? 'Sir');
            return "Hello {$name}, we already have your details. Thank you! If you want to start a new enquiry, just say *hi*.";
        }

        // 1) If starting and user greets ==> ask for phone
        // match common greetings
        if (! $conv->step || $conv->step === 'start') {
            // detect greeting words (hi, hello, hey, hai, hiii, greetings)
            if (preg_match('/\b(hi|hello|hey|hii|hai|helo|greetings)\b/i', $normalizedText)) {
                $conv->step = 'awaiting_phone';
                $conv->save();

                // store prompt in history (outgoing)
                $data = $conv->data ?? [];
                $data['history'][] = [
                    'direction' => 'out',
                    'text'      => "Hi! Please type your phone number (include country code if possible).",
                    'step'      => $conv->step,
                    'time'      => now()->toIso8601String(),
                ];
                $conv->data = $data;
                $conv->save();

                return "Hi! Please type your phone number (include country code if possible).";
            }

            // If it isn't a greeting but we're at start, ask for greeting or phone (fallback)
            $conv->step = 'awaiting_phone';
            $conv->save();
            return "Welcome! Please type your phone number (or say hi).";
        }

        // 2) We're waiting for phone number
        if ($conv->step === 'awaiting_phone') {
            // Normalize digits only
            $digits = preg_replace('/\D+/', '', $text);

            if (! $digits) {
                return "I couldn't detect a phone number. Please send the number digits only, including country code (for example: +919876543210 or 9876543210).";
            }

            // Try multiple lookup strategies:
            //  - exact match
            //  - contains (handles stored numbers with or without country code)
            $user = ConversationUser::where('phone1', $digits)
                ->orWhere('phone2', $digits)
                ->orWhere('phone1', 'like', "%{$digits}")
                ->orWhere('phone2', 'like', "%{$digits}")
                ->first();

            if ($user) {
                // Save name in conversation data for future quick replies
                $conv->step = 'completed';
                $data = $conv->data ?? [];
                $data['name'] = $user->name;
                $data['found_phone'] = $digits;
                $data['history'][] = [
                    'direction' => 'out',
                    'text'      => "Hello {$user->name}, we found your record. How can we help you today?",
                    'step'      => $conv->step,
                    'time'      => now()->toIso8601String(),
                ];
                $conv->data = $data;
                $conv->save();

                return "Hello {$user->name}, we found your record. How can we help you today?";
            }

            // Not found
            return "We couldn't find an account for that number. Please re-send your full phone number with country code (for example: +919876543210).";
        }

        // Default fallback
        return "Sorry, I didn't understand. Please say *hi* to start or send your phone number.";
    }

      

    
    protected function sendWhatsAppText(Setting $settings, string $to, string $text): void
    {
        $phoneNumberId = $settings->phone_number_id;
        $accessToken   = $settings->access_token;

        if (! $phoneNumberId || ! $accessToken) {
            Log::warning('Missing phone_number_id or access_token on Setting', [
                'setting_id' => $settings->id ?? null,
            ]);
            return;
        }

        $url = "https://graph.facebook.com/v21.0/{$phoneNumberId}/messages";

        Http::withToken($accessToken)->post($url, [
            "messaging_product" => "whatsapp",
            "to"                => $to,
            "type"              => "text",
            "text"              => [
                "preview_url" => false,
                "body"        => $text,
            ],
        ]);
    }
}

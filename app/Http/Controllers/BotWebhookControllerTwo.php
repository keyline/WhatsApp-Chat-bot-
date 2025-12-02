<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Conversation;
use App\Models\ConversationUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotWebhookControllerTwo extends Controller
{
    public function handle(Request $request)
    {
        $myVerifyToken = config('services.whatsapp.webhook_verify_token');

        // 1) Webhook verification (GET)
        if ($request->isMethod('get')) {
            $mode      = $request->query('hub_mode');
            $sentToken = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $sentToken === $myVerifyToken) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Invalid verify token', 403);
        }

        // 2) Incoming message (POST)
        $payload = $request->all();
        Log::info('WA POST payload', $payload);

        $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');
        if (! $phoneNumberId) {
            return response()->json(['status' => 'no-phone-number-id']);
        }

        $settings = Setting::where('phone_number_id', $phoneNumberId)->first();
        if (! $settings) {
            Log::warning('No Setting found for phone_number_id', ['phone_number_id' => $phoneNumberId]);
            return response()->json(['status' => 'no-settings']);
        }

        // extract first message object
        $message = data_get($payload, 'entry.0.changes.0.value.messages.0');
        if (! $message) {
            Log::info('No message object in payload', $payload);
            return response()->json(['status' => 'ignored']);
        }

        $from = $message['from'] ?? null;
        if (! $from) {
            return response()->json(['status' => 'no-from']);
        }

        // robustly resolve incoming text (handles text, interactive button/list, contact)
        $text = $this->resolveIncomingText($message);

        Log::info('Incoming WA summary', [
            'from' => $from,
            'type' => $message['type'] ?? null,
            'text' => $text,
        ]);

        // find or create conversation for this WA sender + user
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

        // process flow
        $reply = $this->getReplyForMessage($conversation, $text);

        // store outgoing reply in JSON history
        $data = $conversation->data ?? [];
        $data['history'][] = [
            'direction' => 'out',
            'text'      => $reply,
            'step'      => $conversation->step,
            'time'      => now()->toIso8601String(),
        ];
        $conversation->data = $data;
        $conversation->save();

        // send via WA cloud API & log response
        $this->sendWhatsAppText($settings, $from, $reply);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Resolve interesting text from the incoming message object.
     * Handles text, interactive (button/list), and contact share.
     */
    protected function resolveIncomingText(array $message): string
    {
        // plain text
        $text = data_get($message, 'text.body');

        // interactive (button/list)
        if (! $text) {
            $text = data_get($message, 'interactive.button_reply.title')
                 ?? data_get($message, 'interactive.list_reply.title')
                 ?? data_get($message, 'interactive.type');
        }

        // contact share (extract phone if present)
        if (! $text && data_get($message, 'contacts.0')) {
            $contact = data_get($message, 'contacts.0');
            // try whatsapp id first then explicit phone
            $text = data_get($contact, 'wa_id')
                 ?? data_get($contact, 'phones.0.phone')
                 ?? '';
        }

        return (string) $text;
    }

    /**
     * Main chatbot flow for this controller (greeting -> ask phone -> match -> greet by name).
     */
    protected function getReplyForMessage(Conversation $conv, string $text): string
    {
        $data = $conv->data ?? [];

        // log incoming message into history
        $data['history'][] = [
            'direction' => 'in',
            'text'      => $text,
            'step'      => $conv->step,
            'time'      => now()->toIso8601String(),
        ];
        $conv->data = $data;
        $conv->save();

        $normalizedText = strtolower(trim($text));

        // If conversation already completed, ask to start new if they say hi
        if ($conv->step === 'completed') {
            if (preg_match('/\b(hi|hello|hey|hii|hai)\b/i', $normalizedText)) {
                // restart flow for new enquiry
                $conv->step = 'start';
                $conv->save();
                // Ask for phone again
                return "Hi again! Please type your phone number (include country code if possible).";
            }

            // If already completed and not restarting, greet by stored name if present
            $name = $conv->data['name'] ?? 'Sir';
            return "Hello {$name}, we already have your details. If you want to start a new enquiry, say *hi*.";
        }

        // If starting and user greets → ask for phone
        if (! $conv->step || $conv->step === 'start') {
            if (preg_match('/\b(hi|hello|hey|hii|hai|helo|greetings)\b/i', $normalizedText)) {
                $conv->step = 'awaiting_phone';
                $conv->save();

                // store prompt in history (outgoing)
                // $data = $conv->data ?? [];
                // $data['history'][] = [
                //     'direction' => 'out',
                //     'text'      => "Hi! Please type your phone number.",
                //     'step'      => $conv->step,
                //     'time'      => now()->toIso8601String(),
                // ];
                // $conv->data = $data;
                // $conv->save();

                return "Hi! Please type your phone number.";
            }

            // fallback at start: ask for greeting or phone
            $conv->step = 'awaiting_phone';
            $conv->save();
            return "Welcome! Please type your phone number (or say hi).";
        }

        // Awaiting phone — user should send digits or a contact share
        if ($conv->step === 'awaiting_phone') {
            // if user sent something like "yes" or other word, prompt to send number
            $digits = preg_replace('/\D+/', '', $text);

            if (! $digits) {
                // maybe they shared contact but our resolver didn't catch — ask to share contact explicitly
                return "I couldn't detect a phone number. Please send your phone digits (e.g. +919876543210) or use WhatsApp's Share Contact option.";
            }

            // Robust tolerant lookup: strip non-digits from DB fields and match contains
            $user = ConversationUser::whereRaw(
                    "REPLACE(REPLACE(REPLACE(phone1, ' ', ''), '+', ''), '-', '') LIKE ?",
                    ["%{$digits}%"]
                )
                ->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(phone2, ' ', ''), '+', ''), '-', '') LIKE ?",
                    ["%{$digits}%"]
                )
                ->first();

            if ($user) {
                // Save name for future quick replies and mark completed
                $conv->step = 'completed';
                $data = $conv->data ?? [];
                $data['name'] = $user->name;
                $data['found_phone'] = $digits;
                // $data['history'][] = [
                //     'direction' => 'out',
                //     'text'      => "Hello {$user->name}, we found your record. How can we help you today?",
                //     'step'      => $conv->step,
                //     'time'      => now()->toIso8601String(),
                // ];
                $conv->data = $data;
                $conv->save();

                return "Hello {$user->name}, we found your record. How can we help you today?";
            }

            // Not found — polite guidance
            return "We couldn't find an account for that number. Please re-send your full phone number (for example: 9876543210)"; 
        }

        // default fallback
        return "Sorry, I didn't understand. Please say *hi* to start or send your phone number.";
    }

    /**
     * Send WhatsApp text via Graph API and log response.
     */
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

        $response = Http::withToken($accessToken)->post($url, [
            "messaging_product" => "whatsapp",
            "to"                => $to,
            "type"              => "text",
            "text"              => [
                "preview_url" => false,
                "body"        => $text,
            ],
        ]);

        Log::info('WA send response', [
            'to'     => $to,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
    }
}

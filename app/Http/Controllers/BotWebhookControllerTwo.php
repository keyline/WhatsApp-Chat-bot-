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

            // original phone from conversation
            // $phoneNumber = $conv->phone ?? '';
            // $len = strlen($phoneNumber);

            //     if ($len == 12) {
            //         $newNumb = substr($phoneNumber, 2);
            //     } elseif ($len == 11) {
            //         $newNumb = substr($phoneNumber, 1);
            //     } elseif ($len == 13) {
            //         $newNumb = substr($phoneNumber, 3);
            //     } else {
            //         $newNumb = $phoneNumber; // unchanged
            //     }
               

            // Log incoming message into history
            $data['history'][] = [
                'direction' => 'in',
                'text'      => $text,
                'step'      => $conv->step,
                'time'      => now()->toIso8601String(),
            ];
            $conv->data = $data;
            $conv->save();

            // 🔹 If flow is already completed
            if ($conv->step === 'completed') {
                $normalized = strtolower(trim($text));
                $user = ConversationUser::where('phone1', $normalized)->first();
                 $name = $user->name ?? 'Sir';
                return "Hello " . $name . ", We already have your details. Thank you! If you want to start a new enquiry, just say *hi*.";
            }

            // 1) FIRST TIME: start the flow
            if (! $conv->step || $conv->step === 'start') {
                $conv->step = 'completed';
                 $conv->save();
                return "Please type your phone number ";
            }
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

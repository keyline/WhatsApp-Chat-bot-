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
            ]
        );

        // 4) Decide reply based on conversation state (DYNAMIC)
        $reply = $this->getReplyForMessage($conversation, trim($text), $from);

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
        protected function getReplyForMessage(Conversation $conv, string $text, string $convPhone): string
        {
            $data = $conv->data ?? [];

            // original phone from conversation
            $phoneNumber = $convPhone ?? '';
            $len = strlen($phoneNumber);

                if ($len == 12) {
                    $newNumb = substr($phoneNumber, 2);
                } elseif ($len == 11) {
                    $newNumb = substr($phoneNumber, 1);
                } elseif ($len == 13) {
                    $newNumb = substr($phoneNumber, 3);
                } else {
                    $newNumb = $phoneNumber; // unchanged
                }

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

                // If user says "hi" → start a new enquiry
                if ($normalized === 'hi' || $normalized === 'hello') {
                    $firstKey = 'ask_service';

                    $question = BotQuestion::where('key', $firstKey)->first();

                    if (! $question) {
                        // fallback if DB not configured
                        $conv->step = 'start';
                        $conv->save();

                        return "Let's start a new enquiry.\n"
                            . "Hello! Welcome to Keyline Digitech.\n"
                            . "Please configure bot_questions for key: ask_service.";
                    }

                    $conv->step = $firstKey;
                    $conv->save();

                    return "Let's start a new enquiry.\n" . $question->message;
                }

                // Otherwise keep them in completed
                $user = ConversationUser::where('phone1', $newNumb)->first();
                 $name = $user->name ?? 'there';
                return "Hello" . $name . ", We already have your details. Thank you! If you want to start a new enquiry, just say *hi*.";
            }

            // 1) FIRST TIME: start the flow
            if (! $conv->step || $conv->step === 'start') {
                // First question key (you can change this)
                $firstKey = 'ask_service';

                $question = BotQuestion::where('key', $firstKey)->first();

                if (! $question) {
                    // Fallback if DB not configured
                    $conv->step = 'start';
                    $conv->save();

                    return "Hello! Welcome to Keyline Digitech.\n"
                        . "Please configure bot_questions for key: ask_service.";
                }

                $conv->step = $firstKey;
                $conv->save();

                return $question->message;
            }

            // 2) We are answering CURRENT question = $conv->step
            $currentKey = $conv->step;

            /** @var \App\Models\BotQuestion|null $question */
            $question = BotQuestion::with('options')->where('key', $currentKey)->first();

            if (! $question) {
                // If something wrong in DB, reset conversation
                $conv->step = 'start';
                $conv->save();

                return "Something went wrong in the bot configuration.\nLet's start again.\n"
                    . "Hello! Welcome to Keyline Digitech.";
            }

            // 2a) Store raw answer if this question has store_field (e.g. contact.name)
            if (! empty($question->store_field)) {
                // store in JSON path like 'contact.name', 'contact.email', 'service', etc.
                data_set($data, $question->store_field, $text);
            }

            // 3) Decide next step based on options in DB
            [$nextKey, $data] = $this->applyOptionsAndGetNextKey($question, $data, $text);

            // 3a) If conversation should finish
            if ($nextKey === 'completed') {
                $conv->step = 'completed';
                $conv->data = $data;
                $conv->save();

                return $this->buildSummaryMessage($data);
            }

            // 3b) If no next key found, repeat same question
            if (! $nextKey) {
                $conv->data = $data;
                $conv->save();

                return $question->message ?: "Please reply with a valid option.";
            }

            // 4) Load next question from DB and reply with its message
            $nextQuestion = BotQuestion::where('key', $nextKey)->first();

            if (! $nextQuestion) {
                // If next question missing, fail gracefully
                $conv->step = 'start';
                $conv->data = $data;
                $conv->save();

                return "Bot step '{$nextKey}' is not configured. Please contact the admin.";
            }

            $conv->step = $nextKey;
            $conv->data = $data;
            $conv->save();

            return $nextQuestion->message;
        }

    /**
     * Use bot_options table to decide next step and modify data
     */
    protected function applyOptionsAndGetNextKey(BotQuestion $question, array $data, string $text): array
    {
        $normalized = strtolower($text);
        $options    = $question->options;

        $match = null;

        // 1) Try exact match on match_value (e.g. "1", "2", "yes")
        $match = $options->first(function ($opt) use ($text, $normalized) {
            // You can make this more flexible if needed
            if ($opt->match_value === $text) {
                return true;
            }

            // Optional: text-based yes/no
            if ($opt->match_value === 'yes' && in_array($normalized, ['yes', 'y'])) {
                return true;
            }

            if ($opt->match_value === 'no' && in_array($normalized, ['no', 'n'])) {
                return true;
            }

            return false;
        });

        // 2) If no exact match, try default option (is_default = 1)
        if (! $match) {
            $match = $options->firstWhere('is_default', true);
        }

        $nextKey = null;

        if ($match) {
            $nextKey = $match->next_key ?: null;

            // If this option sets service (website, mobile_app, etc.)
            if (! empty($match->set_service)) {
                $data['service'] = $match->set_service;
            }

            // If this option stores a fixed value in JSON
            if (! empty($match->store_field) && ! empty($match->store_value)) {
                data_set($data, $match->store_field, $match->store_value);
            }
        }

        return [$nextKey, $data];
    }

    /**
     * Build final summary message (you can keep this static or later move to DB)
     */
    protected function buildSummaryMessage(array $data): string
    {
        $service = $data['service'] ?? '-';

        $websiteType   = $data['website']['type']           ?? null;
        $websiteProj   = $data['website']['project_type']   ?? null;
        $appPlatform   = $data['mobile_app']['platform']    ?? null;
        $appPurpose    = $data['mobile_app']['purpose']     ?? null;
        $dmService     = $data['digital_marketing']['service'] ?? null;
        $dmGoal        = $data['digital_marketing']['goal']    ?? null;
        $brandService  = $data['branding']['service']       ?? null;
        $brandRef      = $data['branding']['reference']     ?? null;

        $name          = $data['contact']['name']           ?? '-';
        $bizName       = $data['contact']['business_name']  ?? '-';
        $phone         = $data['contact']['phone']          ?? '-';
        $email         = $data['contact']['email']          ?? '-';

        $detailsLines = [];

        if ($websiteType || $websiteProj) {
            $detailsLines[] = "Website type: " . ($websiteType ?: '-');
            $detailsLines[] = "Project type: " . ($websiteProj ?: '-');
        }

        if ($appPlatform || $appPurpose) {
            $detailsLines[] = "App platform: " . ($appPlatform ?: '-');
            $detailsLines[] = "App purpose: " . ($appPurpose ?: '-');
        }

        if ($dmService || $dmGoal) {
            $detailsLines[] = "Digital Marketing service: " . ($dmService ?: '-');
            $detailsLines[] = "Goal: " . ($dmGoal ?: '-');
        }

        if ($brandService || $brandRef) {
            $detailsLines[] = "Branding service: " . ($brandService ?: '-');
            $detailsLines[] = "Reference: " . ($brandRef ?: '-');
        }

        $detailsText = implode("\n", $detailsLines);

        return "Thank you for the details! 🙏\n"
            . "We will connect with you shortly.\n\n"
            . "Summary of your request:\n"
            . "Service: {$service}\n"
            . ($detailsText ? $detailsText . "\n" : '')
            . "Name: {$name}\n"
            . "Business: {$bizName}\n"
            . "Phone: {$phone}\n"
            . "Email: {$email}";
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

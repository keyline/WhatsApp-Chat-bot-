<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Conversation;
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
            ]
        );

        // 4) Decide reply based on conversation state
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
     * MAIN CHATBOT FLOW (everything in $conv->data JSON)
     */
    protected function getReplyForMessage(Conversation $conv, string $text): string
    {
        $normalized = strtolower($text);
        $data       = $conv->data ?? [];

        // Current service from JSON
        $service = $data['service'] ?? null;

        // Log incoming message into history
        $data['history'][] = [
            'direction' => 'in',
            'text'      => $text,
            'step'      => $conv->step,
            'time'      => now()->toIso8601String(),
        ];

        // -------- STEP 1: GREETING / SERVICE SELECTION --------
        if ($conv->step === 'start') {
            $conv->step = 'ask_service';
            $conv->data = $data;
            $conv->save();

            return "Hello! Welcome to Keyline Digitech.\n"
                . "How can we help you grow your business today?\n"
                . "Please tell us what service you are looking for (type the number):\n"
                . "1️⃣ Website Development\n"
                . "2️⃣ Mobile App Development\n"
                . "3️⃣ Digital Marketing\n"
                . "4️⃣ Branding & Creative Design";
        }

        // -------- STEP 2: USER CHOOSES SERVICE --------
        if ($conv->step === 'ask_service') {
            if (in_array($text, ['1', '1️⃣'])) {
                $data['service'] = 'website';
                $conv->step      = 'web_type';
                $conv->data      = $data;
                $conv->save();

                return "Great! You’ve selected Website Development.\n"
                    . "May I know what type of website you need?\n"
                    . "Please choose one:\n"
                    . "1️⃣ Business Website\n"
                    . "2️⃣ Ecommerce Website\n"
                    . "3️⃣ Portfolio / Personal Website\n"
                    . "4️⃣ Custom Web Application";
            }

            if (in_array($text, ['2', '2️⃣'])) {
                $data['service'] = 'mobile_app';
                $conv->step      = 'app_platform';
                $conv->data      = $data;
                $conv->save();

                return "Awesome! You’ve selected Mobile App Development.\n"
                    . "Which platform do you want to build your app on?\n"
                    . "1️⃣ Android\n"
                    . "2️⃣ iOS\n"
                    . "3️⃣ Both Android & iOS";
            }

            if (in_array($text, ['3', '3️⃣'])) {
                $data['service'] = 'digital_marketing';
                $conv->step      = 'dm_service';
                $conv->data      = $data;
                $conv->save();

                return "Great choice! You’ve selected Digital Marketing.\n"
                    . "Which service are you looking for?\n"
                    . "1️⃣ SEO\n"
                    . "2️⃣ Google Ads\n"
                    . "3️⃣ Social Media Marketing\n"
                    . "4️⃣ Performance Marketing (Leads/Sales)\n"
                    . "5️⃣ Influencer Marketing";
            }

            if (in_array($text, ['4', '4️⃣'])) {
                $data['service'] = 'branding';
                $conv->step      = 'brand_service';
                $conv->data      = $data;
                $conv->save();

                return "Great! You’re interested in Branding & Creative Design.\n"
                    . "What type of creative service do you need?\n"
                    . "1️⃣ Logo Design\n"
                    . "2️⃣ Branding Package (Logo + Identity + Guidelines)\n"
                    . "3️⃣ Social Media Creatives\n"
                    . "4️⃣ Advertising Creatives / Campaign Design";
            }

            $conv->data = $data;
            $conv->save();

            return "Please reply with:\n1 for Website Development\n2 for Mobile App Development\n3 for Digital Marketing\n4 for Branding & Creative Design.";
        }

        // Recalculate service in case it was just set
        $service = $data['service'] ?? null;

        // ===== WEBSITE BRANCH =====
        if ($service === 'website') {
            if ($conv->step === 'web_type') {
                $map = [
                    '1' => 'Business Website',
                    '2' => 'Ecommerce Website',
                    '3' => 'Portfolio / Personal Website',
                    '4' => 'Custom Web Application',
                ];

                if (! isset($map[$text])) {
                    $conv->data = $data;
                    $conv->save();

                    return "Please choose a valid option:\n"
                        . "1 Business Website\n"
                        . "2 Ecommerce Website\n"
                        . "3 Portfolio / Personal Website\n"
                        . "4 Custom Web Application";
                }

                $data['website']['type'] = $map[$text];
                $conv->step              = 'web_existing';
                $conv->data              = $data;
                $conv->save();

                return "Do you already have a website, or is this a new project?\n"
                    . "1️⃣ New Website\n"
                    . "2️⃣ Redesign Existing Website";
            }

            if ($conv->step === 'web_existing') {
                if (str_contains($normalized, 'new') || $text === '1') {
                    $data['website']['project_type'] = 'New Website';
                } elseif (str_contains($normalized, 'redesign') || $text === '2') {
                    $data['website']['project_type'] = 'Redesign Existing Website';
                } else {
                    $conv->data = $data;
                    $conv->save();

                    return "Please reply with:\n- New Website (or 1)\n- Redesign Existing Website (or 2)";
                }

                $conv->step = 'ask_contact_name';
                $conv->data = $data;
                $conv->save();

                return $this->contactIntro() . "\n\n1️⃣ Your Name:";
            }
        }

        // ===== MOBILE APP BRANCH =====
        if ($service === 'mobile_app') {
            if ($conv->step === 'app_platform') {
                $map = [
                    '1' => 'Android',
                    '2' => 'iOS',
                    '3' => 'Both Android & iOS',
                ];

                if (! isset($map[$text])) {
                    $conv->data = $data;
                    $conv->save();

                    return "Please choose:\n1 Android\n2 iOS\n3 Both Android & iOS";
                }

                $data['mobile_app']['platform'] = $map[$text];
                $conv->step                     = 'app_purpose';
                $conv->data                     = $data;
                $conv->save();

                return "What is the main purpose of the app?\n"
                    . "For example: booking, ecommerce, service app, delivery, education, etc.";
            }

            if ($conv->step === 'app_purpose') {
                $data['mobile_app']['purpose'] = $text; // free text
                $conv->step                    = 'ask_contact_name';
                $conv->data                    = $data;
                $conv->save();

                return $this->contactIntro() . "\n\n1️⃣ Your Name:";
            }
        }

        // ===== DIGITAL MARKETING BRANCH =====
        if ($service === 'digital_marketing') {
            if ($conv->step === 'dm_service') {
                $map = [
                    '1' => 'SEO',
                    '2' => 'Google Ads',
                    '3' => 'Social Media Marketing',
                    '4' => 'Performance Marketing (Leads/Sales)',
                    '5' => 'Influencer Marketing',
                ];

                if (! isset($map[$text])) {
                    $conv->data = $data;
                    $conv->save();

                    return "Please choose one service:\n"
                        . "1 SEO\n2 Google Ads\n3 Social Media Marketing\n4 Performance Marketing\n5 Influencer Marketing";
                }

                $data['digital_marketing']['service'] = $map[$text];
                $conv->step                           = 'dm_goal';
                $conv->data                           = $data;
                $conv->save();

                return "What is your primary goal?\n"
                    . "1️⃣ More leads\n"
                    . "2️⃣ More sales\n"
                    . "3️⃣ Increase website traffic\n"
                    . "4️⃣ Build brand awareness";
            }

            if ($conv->step === 'dm_goal') {
                $data['digital_marketing']['goal'] = $text;
                $conv->step                        = 'ask_contact_name';
                $conv->data                        = $data;
                $conv->save();

                return $this->contactIntro() . "\n\n1️⃣ Your Name:";
            }
        }

        // ===== BRANDING BRANCH =====
        if ($service === 'branding') {
            if ($conv->step === 'brand_service') {
                $map = [
                    '1' => 'Logo Design',
                    '2' => 'Branding Package (Logo + Identity + Guidelines)',
                    '3' => 'Social Media Creatives',
                    '4' => 'Advertising Creatives / Campaign Design',
                ];

                if (! isset($map[$text])) {
                    $conv->data = $data;
                    $conv->save();

                    return "Please choose:\n"
                        . "1 Logo Design\n"
                        . "2 Branding Package\n"
                        . "3 Social Media Creatives\n"
                        . "4 Advertising Creatives / Campaign Design";
                }

                $data['branding']['service'] = $map[$text];
                $conv->step                  = 'brand_reference';
                $conv->data                  = $data;
                $conv->save();

                return "Do you have any reference style or brand guideline you want us to follow?\n"
                    . "1️⃣ Yes\n"
                    . "2️⃣ No";
            }

            if ($conv->step === 'brand_reference') {
                if (str_contains($normalized, 'yes') || $text === '1') {
                    $data['branding']['reference'] = 'Has reference';
                } elseif (str_contains($normalized, 'no') || $text === '2') {
                    $data['branding']['reference'] = 'No reference';
                } else {
                    $conv->data = $data;
                    $conv->save();

                    return "Please reply Yes or No (or 1 / 2).";
                }

                $conv->step = 'ask_contact_name';
                $conv->data = $data;
                $conv->save();

                return $this->contactIntro() . "\n\n1️⃣ Your Name:";
            }
        }

        // ===== COMMON CONTACT INFO STEPS =====
        if ($conv->step === 'ask_contact_name') {
            $data['contact']['name'] = $text;
            $conv->step              = 'ask_business_name';
            $conv->data              = $data;
            $conv->save();

            return "2️⃣ Business Name:";
        }

        if ($conv->step === 'ask_business_name') {
            $data['contact']['business_name'] = $text;
            $conv->step                       = 'ask_contact_number';
            $conv->data                       = $data;
            $conv->save();

            return "3️⃣ Contact Number:";
        }

        if ($conv->step === 'ask_contact_number') {
            $data['contact']['phone'] = $text;
            $conv->step               = 'ask_email';
            $conv->data               = $data;
            $conv->save();

            return "4️⃣ Email ID:";
        }

        if ($conv->step === 'ask_email') {
            if (! filter_var($text, FILTER_VALIDATE_EMAIL)) {
                $conv->data = $data;
                $conv->save();

                return "Please send a valid email ID.";
            }

            $data['contact']['email'] = $text;
            $conv->step               = 'completed';
            $conv->data               = $data;
            $conv->save();

            // Build summary from JSON
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

        if ($conv->step === 'completed') {
            $conv->data = $data;
            $conv->save();

            return "We already have your details. Thank you! If you want to start a new enquiry, just say *hi*.";
        }

        // Fallback – reset
        $conv->step = 'start';
        $conv->data = $data;
        $conv->save();

        return "Let's start again.\n"
            . "Hello! Welcome to Keyline Digitech.\n"
            . "Please reply:\n1 Website Development\n2 Mobile App Development\n3 Digital Marketing\n4 Branding & Creative Design.";
    }

    protected function contactIntro(): string
    {
        return "Thank you for the details! To assist you further, may I have your basic contact information?\n"
            . "Please share the following one by one.";
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

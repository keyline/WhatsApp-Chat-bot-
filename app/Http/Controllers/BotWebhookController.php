<?php

namespace App\Http\Controllers;

// use App\Models\Setting;
// use App\Models\Conversation;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;

// class BotWebhookController extends Controller
// {
//     public function handle(Request $request, string $token)
//     {
//         // 1) Which organization?
//         $settings = Setting::where('verify_token', $token)->first();

//         if (! $settings) {
//             return response()->json(['error' => 'Invalid bot token'], 401);
//         }

//         // 2) Parse WhatsApp Cloud API payload (adjust if needed)
//         $payload = $request->all();

//         $message = data_get($payload, 'entry.0.changes.0.value.messages.0');
//         if (!$message) {
//             return response()->json(['status' => 'ignored']);
//         }

//         $from = $message['from'] ?? null;
//         $text = $message['text']['body'] ?? '';

//         if (!$from) {
//             return response()->json(['status' => 'no-from']);
//         }

//         // 3) Find or create conversation
//         $conversation = Conversation::firstOrCreate(
//             [
//                 'user_id' => $settings->user_id,
//                 'phone'   => $from,
//             ],
//             [
//                 'step' => 'start',
//             ]
//         );

//         $reply = $this->getReplyForMessage($conversation, trim($text));

//         // 4) Send reply back via WhatsApp
//         $this->sendWhatsAppText($settings, $from, $reply);

//         return response()->json(['status' => 'ok']);

//         //     return response()->json([
//         // 'ok'    => true,
//         // 'token' => $token,
//         // 'data'  => $request->all(),
//         //  ]);
//     }

//     protected function getReplyForMessage(Conversation $conv, string $text): string
//     {
//         $normalized = strtolower($text);

//         // -------- STEP 1: GREETING / SERVICE SELECTION --------
//         if ($conv->step === 'start') {
//             $conv->step = 'ask_service';
//             $conv->save();

//             return "Hello! Welcome to Keyline Digitech.\n"
//                 . "How can we help you grow your business today?\n"
//                 . "Please tell us what service you are looking for:\n"
//                 . "1️⃣ Website Development\n"
//                 . "2️⃣ Mobile App Development\n"
//                 . "3️⃣ Digital Marketing\n"
//                 . "4️⃣ Branding & Creative Design";
//         }

//         // -------- STEP 2: USER CHOOSES SERVICE --------
//         if ($conv->step === 'ask_service') {
//             if (in_array($text, ['1', '1️⃣'])) {
//                 $conv->service = 'website';
//                 $conv->step    = 'web_type';
//                 $conv->save();

//                 return "Great! You’ve selected Website Development.\n"
//                     . "May I know what type of website you need?\n"
//                     . "Please choose one:\n"
//                     . "1. Business Website\n"
//                     . "2. Ecommerce Website\n"
//                     . "3. Portfolio / Personal Website\n"
//                     . "4. Custom Web Application";
//             }

//             if (in_array($text, ['2', '2️⃣'])) {
//                 $conv->service = 'mobile_app';
//                 $conv->step    = 'app_platform';
//                 $conv->save();

//                 return "Awesome! You’ve selected Mobile App Development.\n"
//                     . "Which platform do you want to build your app on?\n"
//                     . "1. Android\n"
//                     . "2. iOS\n"
//                     . "3. Both Android & iOS";
//             }

//             if (in_array($text, ['3', '3️⃣'])) {
//                 $conv->service = 'digital_marketing';
//                 $conv->step    = 'dm_service';
//                 $conv->save();

//                 return "Great choice! You’ve selected Digital Marketing.\n"
//                     . "Which service are you looking for?\n"
//                     . "1. SEO\n"
//                     . "2. Google Ads\n"
//                     . "3. Social Media Marketing\n"
//                     . "4. Performance Marketing (Leads/Sales)\n"
//                     . "5. Influencer Marketing";
//             }

//             if (in_array($text, ['4', '4️⃣'])) {
//                 $conv->service = 'branding';
//                 $conv->step    = 'brand_service';
//                 $conv->save();

//                 return "Great! You’re interested in Branding & Creative Design.\n"
//                     . "What type of creative service do you need?\n"
//                     . "1. Logo Design\n"
//                     . "2. Branding Package (Logo + Identity + Guidelines)\n"
//                     . "3. Social Media Creatives\n"
//                     . "4. Advertising Creatives / Campaign Design";
//             }

//             return "Please reply with:\n1 for Website Development\n2 for Mobile App Development\n3 for Digital Marketing\n4 for Branding & Creative Design.";
//         }

//         // ===== WEBSITE BRANCH =====
//         if ($conv->service === 'website') {
//             if ($conv->step === 'web_type') {
//                 $map = [
//                     '1' => 'Business Website',
//                     '2' => 'Ecommerce Website',
//                     '3' => 'Portfolio / Personal Website',
//                     '4' => 'Custom Web Application',
//                 ];

//                 if (!isset($map[$text])) {
//                     return "Please choose a valid option:\n1 Business Website\n2 Ecommerce Website\n3 Portfolio / Personal Website\n4 Custom Web Application";
//                 }

//                 $conv->option1 = $map[$text];
//                 $conv->step    = 'web_existing';
//                 $conv->save();

//                 return "Do you already have a website, or is this a new project?\n"
//                     . "• New Website\n"
//                     . "• Redesign Existing Website";
//             }

//             if ($conv->step === 'web_existing') {
//                 if (str_contains($normalized, 'new')) {
//                     $conv->option2 = 'New Website';
//                 } elseif (str_contains($normalized, 'redesign')) {
//                     $conv->option2 = 'Redesign Existing Website';
//                 } else {
//                     // accept 1/2 as shortcut
//                     if ($text === '1') {
//                         $conv->option2 = 'New Website';
//                     } elseif ($text === '2') {
//                         $conv->option2 = 'Redesign Existing Website';
//                     } else {
//                         return "Please reply with:\n- New Website\n- Redesign Existing Website";
//                     }
//                 }

//                 $conv->step = 'ask_contact_name';
//                 $conv->save();

//                 return $this->contactIntro() . "\n\n"
//                     . "1️⃣ Your Name:";
//             }
//         }

//         // ===== MOBILE APP BRANCH =====
//         if ($conv->service === 'mobile_app') {
//             if ($conv->step === 'app_platform') {
//                 $map = [
//                     '1' => 'Android',
//                     '2' => 'iOS',
//                     '3' => 'Both Android & iOS',
//                 ];

//                 if (!isset($map[$text])) {
//                     return "Please choose:\n1 Android\n2 iOS\n3 Both Android & iOS";
//                 }

//                 $conv->option1 = $map[$text];
//                 $conv->step    = 'app_purpose';
//                 $conv->save();

//                 return "What is the main purpose of the app?\n"
//                     . "For example: booking, ecommerce, service app, delivery, education, etc.";
//             }

//             if ($conv->step === 'app_purpose') {
//                 $conv->option2 = $text; // free text
//                 $conv->step    = 'ask_contact_name';
//                 $conv->save();

//                 return $this->contactIntro() . "\n\n"
//                     . "1️⃣ Your Name:";
//             }
//         }

//         // ===== DIGITAL MARKETING BRANCH =====
//         if ($conv->service === 'digital_marketing') {
//             if ($conv->step === 'dm_service') {
//                 $map = [
//                     '1' => 'SEO',
//                     '2' => 'Google Ads',
//                     '3' => 'Social Media Marketing',
//                     '4' => 'Performance Marketing (Leads/Sales)',
//                     '5' => 'Influencer Marketing',
//                 ];

//                 if (!isset($map[$text])) {
//                     return "Please choose one service:\n1 SEO\n2 Google Ads\n3 Social Media Marketing\n4 Performance Marketing\n5 Influencer Marketing";
//                 }

//                 $conv->option1 = $map[$text];
//                 $conv->step    = 'dm_goal';
//                 $conv->save();

//                 return "What is your primary goal?\n"
//                     . "• More leads\n"
//                     . "• More sales\n"
//                     . "• Increase website traffic\n"
//                     . "• Build brand awareness";
//             }

//             if ($conv->step === 'dm_goal') {
//                 $conv->option2 = $text; // store as entered
//                 $conv->step    = 'ask_contact_name';
//                 $conv->save();

//                 return $this->contactIntro() . "\n\n"
//                     . "1️⃣ Your Name:";
//             }
//         }

//         // ===== BRANDING BRANCH =====
//         if ($conv->service === 'branding') {
//             if ($conv->step === 'brand_service') {
//                 $map = [
//                     '1' => 'Logo Design',
//                     '2' => 'Branding Package (Logo + Identity + Guidelines)',
//                     '3' => 'Social Media Creatives',
//                     '4' => 'Advertising Creatives / Campaign Design',
//                 ];

//                 if (!isset($map[$text])) {
//                     return "Please choose:\n1 Logo Design\n2 Branding Package\n3 Social Media Creatives\n4 Advertising Creatives / Campaign Design";
//                 }

//                 $conv->option1 = $map[$text];
//                 $conv->step    = 'brand_reference';
//                 $conv->save();

//                 return "Do you have any reference style or brand guideline you want us to follow?\n"
//                     . "• Yes\n"
//                     . "• No";
//             }

//             if ($conv->step === 'brand_reference') {
//                 if (str_contains($normalized, 'yes') || $text === '1') {
//                     $conv->option2 = 'Has reference';
//                 } elseif (str_contains($normalized, 'no') || $text === '2') {
//                     $conv->option2 = 'No reference';
//                 } else {
//                     return "Please reply Yes or No.";
//                 }

//                 $conv->step = 'ask_contact_name';
//                 $conv->save();

//                 return $this->contactIntro() . "\n\n"
//                     . "1️⃣ Your Name:";
//             }
//         }

//         // ===== COMMON CONTACT INFO STEPS =====
//         if ($conv->step === 'ask_contact_name') {
//             $conv->name = $text;
//             $conv->step = 'ask_business_name';
//             $conv->save();

//             return "2️⃣ Business Name:";
//         }

//         if ($conv->step === 'ask_business_name') {
//             $conv->business_name = $text;
//             $conv->step          = 'ask_contact_number';
//             $conv->save();

//             return "3️⃣ Contact Number:";
//         }

//         if ($conv->step === 'ask_contact_number') {
//             $conv->contact_number = $text;
//             $conv->step           = 'ask_email';
//             $conv->save();

//             return "4️⃣ Email ID:";
//         }

//         if ($conv->step === 'ask_email') {
//             if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
//                 return "Please send a valid email ID.";
//             }

//             $conv->email = $text;
//             $conv->step  = 'completed';
//             $conv->save();

//             // Final thank you
//             return "Thank you for the details! 🙏\n"
//                 . "We will connect with you shortly.\n\n"
//                 . "Summary of your request:\n"
//                 . "Service: {$conv->service}\n"
//                 . "Details 1: {$conv->option1}\n"
//                 . "Details 2: {$conv->option2}\n"
//                 . "Name: {$conv->name}\n"
//                 . "Business: {$conv->business_name}\n"
//                 . "Phone: {$conv->contact_number}\n"
//                 . "Email: {$conv->email}";
//         }

//         if ($conv->step === 'completed') {
//             return "We already have your details. Thank you! If you want to start a new enquiry, just say *hi*.";
//         }

//         // Fallback
//         $conv->step = 'start';
//         $conv->save();

//         return "Let's start again.\n"
//             . "Hello! Welcome to Keyline Digitech.\n"
//             . "Please reply:\n1 Website Development\n2 Mobile App Development\n3 Digital Marketing\n4 Branding & Creative Design.";
//     }

//     protected function contactIntro(): string
//     {
//         return "Thank you for the details! To assist you further, may I have your basic contact information?\n"
//             . "Please share the following one by one.";
//     }

//     protected function sendWhatsAppText(Setting $settings, string $to, string $text): void
//     {
//         // Adjust to your settings table column names
//         $phoneNumberId = $settings->phone_number_id; // e.g. "123456789"
//         $accessToken   = $settings->access_token;

//         if (!$phoneNumberId || !$accessToken) {
//             return; // or log error
//         }

//         $url = "https://graph.facebook.com/v21.0/{$phoneNumberId}/messages";

//         Http::withToken($accessToken)->post($url, [
//             "messaging_product" => "whatsapp",
//             "to"                => $to,
//             "type"              => "text",
//             "text"              => [
//                 "preview_url" => false,
//                 "body"        => $text,
//             ],
//         ]);
//     }
// }


// use App\Models\Setting;
// use App\Models\Conversation;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;

// class BotWebhookController extends Controller
// {
//     public function handle(Request $request, string $token)
//     {
//         // Find settings row by verify_token coming from URL
//         $settings = Setting::where('verify_token', $token)->first();

//         // If nothing matches this URL token, reject
//         if (! $settings) {
//             return response()->json(['error' => 'Invalid webhook token'], 401);
//         }

//         /**
//          * 1️⃣ META WEBHOOK VERIFICATION (GET)
//          *    Meta calls this when you first 'Verify and Save' the webhook.
//          */
//         if ($request->isMethod('get')) {
//             $mode      = $request->input('hub_mode') ?? $request->input('hub.mode');
//             $sentToken = $request->input('hub_verify_token') ?? $request->input('hub.verify_token');
//             $challenge = $request->input('hub_challenge') ?? $request->input('hub.challenge');

//             // Check mode and verify_token
//             if ($mode === 'subscribe' && $sentToken === $settings->verify_token) {
//                 // Must return challenge as plain text with 200 status
//                 return response($challenge, 200);
//             }

//             return response('Invalid verify token', 403);
//         }

//         /**
//          * 2️⃣ INCOMING WHATSAPP MESSAGE (POST)
//          *    Meta sends this whenever a user sends a message.
//          */
//         $payload = $request->all();

//         // Safely extract the first message from the payload
//         $message = data_get($payload, 'entry.0.changes.0.value.messages.0');

//         if (! $message) {
//             // Nothing interesting in this webhook – just acknowledge
//             return response()->json(['status' => 'ignored']);
//         }

//         $from = $message['from'] ?? null;
//         $text = $message['text']['body'] ?? '';

//         if (! $from) {
//             return response()->json(['status' => 'no-from']);
//         }

//         // 3️⃣ Find or create a conversation for this user & phone
//         $conversation = Conversation::firstOrCreate(
//             [
//                 'user_id' => $settings->user_id,
//                 'phone'   => $from,
//             ],
//             [
//                 'step' => 'start',
//             ]
//         );

//         // 4️⃣ Decide reply based on conversation state
//         $reply = $this->getReplyForMessage($conversation, trim($text));

//         // 5️⃣ Send reply back via WhatsApp Cloud API
//         $this->sendWhatsAppText($settings, $from, $reply);

//         return response()->json(['status' => 'ok']);
//     }

//     // ... keep your getReplyForMessage() exactly as you already have ...

//     protected function contactIntro(): string
//     {
//         return "Thank you for the details! To assist you further, may I have your basic contact information?\n"
//             . "Please share the following one by one.";
//     }

//     protected function sendWhatsAppText(Setting $settings, string $to, string $text): void
//     {
//         $phoneNumberId = $settings->phone_number_id;  // matches your DB column
//         $accessToken   = $settings->access_token;     // matches your DB column

//         if (! $phoneNumberId || ! $accessToken) {
//             return; // or log an error
//         }

//         $url = "https://graph.facebook.com/v21.0/{$phoneNumberId}/messages";

//         Http::withToken($accessToken)->post($url, [
//             "messaging_product" => "whatsapp",
//             "to"                => $to,
//             "type"              => "text",
//             "text"              => [
//                 "preview_url" => false,
//                 "body"        => $text,
//             ],
//         ]);
//     }
// }





use App\Models\Setting;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BotWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1️⃣ WEBHOOK VERIFICATION (GET)
        if ($request->isMethod('get')) {
            $mode      = $request->input('hub_mode') ?? $request->input('hub.mode');
            $sentToken = $request->input('hub_verify_token') ?? $request->input('hub.verify_token');
            $challenge = $request->input('hub_challenge') ?? $request->input('hub.challenge');

            // Look up your settings row by verify_token from Meta
            $settings = Setting::where('verify_token', $sentToken)->first();

            if ($mode === 'subscribe' && $settings) {
                // Must return challenge as plain text
                return response($challenge, 200);
            }

            return response('Invalid verify token', 403);
        }

        // 2️⃣ INCOMING MESSAGE (POST)
        $payload = $request->all();

        $message = data_get($payload, 'entry.0.changes.0.value.messages.0');
        
        if (! $message) {
            return response()->json(['status' => 'ignored']);
        }

        $from = $message['from'] ?? null;
        $text = $message['text']['body'] ?? '';

        if (! $from) {
            return response()->json(['status' => 'no-from']);
        }

        // Get settings from something in payload (e.g. phone_number_id) or single row
        $settings = Setting::first(); // temporary simple version

        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $settings->user_id,
                'phone'   => $from,
            ],
            ['step' => 'start']
        );

        $reply = $this->getReplyForMessage($conversation, trim($text));

        $this->sendWhatsAppText($settings, $from, $reply);

        return response()->json(['status' => 'ok']);
    }

    // keep your getReplyForMessage() and sendWhatsAppText() as you already have
}

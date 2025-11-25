<?php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use App\Models\Setting;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BotController extends Controller
{
     public function dashboard()
   {
        $userId = auth()->id(); // or however you store current user

        // Bot / webhook settings for this user
        $settings = Setting::firstOrCreate(
            ['user_id' => $userId],
            ['verify_token' => Str::random(32)]
        );

        // Your webhook URL (from earlier)
        // $webhookUrl = route('bot.webhook', ['token' => $settings->bot_token]);

        // All conversations for this user
        $conversations = Conversation::where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate(20); // pagination
        //  dd($conversations); die;
        return view('bot_flows.bot-settings', [
            'settings'       => $settings,
            'conversations'  => $conversations,
        ]);
   }


    // public function update(Request $request)
    // {
    //     $request->validate([
    //         'bot_token' => ['required', 'string', 'min:8', 'max:64', 'alpha_dash'],
    //     ]);

    //     $settings = Setting::where('user_id', Auth::id())->firstOrFail();
    //     $settings->verify_token = $request->verify_token;
    //     $settings->save();

    //     return redirect()
    //         ->route('bot.settings.edit')
    //         ->with('success', 'Bot token updated successfully.');
    // }
}

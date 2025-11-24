<?php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BotController extends Controller
{
    // public function index()
    // {
    //     // If you use auth, filter by logged in user
    //     $userId = Auth::id();

    //     $bots = BotSetting::when($userId, fn($q) => $q->where('user_id', $userId))
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return view('bot_flows.index', compact('bots'));
    // }

     public function edit()
    {
        $user = Auth::user();

        $settings = Setting::firstOrCreate(
            ['user_id' => $user->id],
            ['verify_token' => Str::random(32)]
        );

        $webhookUrl = route('bot.webhook', ['token' => $settings->verify_token]);

        return view('bot-settings', compact('settings', 'webhookUrl'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'bot_token' => ['required', 'string', 'min:8', 'max:64', 'alpha_dash'],
        ]);

        $settings = Setting::where('user_id', Auth::id())->firstOrFail();
        $settings->verify_token = $request->verify_token;
        $settings->save();

        return redirect()
            ->route('bot.settings.edit')
            ->with('success', 'Bot token updated successfully.');
    }
}

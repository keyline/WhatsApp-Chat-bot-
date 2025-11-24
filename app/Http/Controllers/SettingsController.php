<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;       
use App\Models\Setting;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Get or create a blank settings row for this user
        $settings = Setting::firstOrCreate(
            ['user_id' => $userId],
            []
        );

        // Simple plan & usage stats (you can change numbers later)
        $monthlyLimit = 10000;

        $usedThisMonth = Message::where('user_id', $userId)
            ->where('direction', 'OUT')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $planName = 'Starter';

        // Team – for now just show current user
        $owner = Auth::user();
        $teamMembers = User::where('id', '!=', $owner->id)->get();

        return view('settings.index', compact(
            'settings',
            'monthlyLimit',
            'usedThisMonth',
            'planName',
            'owner',
            'teamMembers'
        ));
    }

    public function saveApi(Request $request)
    {
        
        $data = $request->validate([
            'business_account_id' => ['required', 'string', 'max:255'],
            'phone_number_id'     => ['required', 'string', 'max:255'],
            'whatsapp_number'     => ['required', 'string', 'max:30'],
            'access_token'        => ['required', 'string'],
        ]);
        // echo "hello";
        // dd($data); die;
        // Example: per-user settings
        $settings = Setting::updateOrCreate(
            ['user_id' => auth()->id()],  // condition
            $data                           // values to set/update
        );

        return back()->with('success', 'WhatsApp API credentials saved successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignTarget;
use App\Models\Message;
use App\Models\BotSetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 📌 Total campaigns
        $total_campaigns = Campaign::where('user_id', $userId)->count();

        // 📌 Active bots count
        $active_bots = BotSetting::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        // 📌 Messages sent (last 24 hours)
        $messages_last_24h = Message::where('user_id', $userId)
            ->where('direction', 'OUT')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // 📌 Delivery Rate calculation
        $delivered = Message::where('user_id', $userId)
            ->where('status', 'delivered')
            ->count();

        $sent = Message::where('user_id', $userId)
            ->where('direction', 'OUT')
            ->count();

        $delivery_rate = $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0;

        // 📌 Recent 3 campaigns with stats
        $recent_campaigns = Campaign::where('user_id', $userId)
            ->latest()
            ->take(3)
            ->withCount([
                'targets as sent_count' => fn($q) => $q->where('send_status', 'sent'),
                'targets as read_count' => fn($q) => $q->where('send_status', 'read'),
                'targets as reply_count' => fn($q) => $q->whereHas('contact', function () {
                    // replies = inbound messages
                }),
            ])
            ->get();

        return view('dashboard.index', compact(
            'total_campaigns',
            'active_bots',
            'messages_last_24h',
            'delivery_rate',
            'recent_campaigns'
        ));
    }
}

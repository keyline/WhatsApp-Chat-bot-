<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChatInboxController extends Controller
{
    // LEFT SIDE: list of all numbers
    public function index()
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get();

        return view('bot_flows.inbox', compact('conversations'));
    }

    // RIGHT SIDE: chat history for one conversation (JSON)
    public function history(Conversation $conversation)
    {
        $userId = Auth::id();
        abort_if($conversation->user_id !== $userId, 403);

        $data    = $conversation->data ?? [];
        $history = $data['history'] ?? [];

        // normalise output a bit
        $messages = collect($history)->map(function ($item) {
            return [
                'direction' => $item['direction'] ?? 'in',
                'text'      => $item['text'] ?? '',
                'time'      => $item['time'] ?? null,
                'step'      => $item['step'] ?? null,
            ];
        })->values();

        return response()->json([
            'conversation' => [
                'id'    => $conversation->id,
                'phone' => $conversation->phone,
                'name'  => $conversation->name, // from accessor
            ],
            'messages' => $messages,
        ]);
    }

    // SEND A NEW MESSAGE (manual, from your dashboard)
    public function send(Request $request, Conversation $conversation)
    {
        $request->validate([
            'text' => ['required', 'string'],
        ]);

        $userId = Auth::id();
        abort_if($conversation->user_id !== $userId, 403);

        $settings = Setting::where('user_id', $userId)->firstOrFail();

        $phoneNumberId = $settings->phone_number_id;
        $accessToken   = $settings->access_token;
        $to            = $conversation->phone;
        $text          = $request->input('text');

        // 1) send through WhatsApp Cloud API
        Http::withToken($accessToken)->post(
            "https://graph.facebook.com/v21.0/{$phoneNumberId}/messages",
            [
                "messaging_product" => "whatsapp",
                "to"                => $to,
                "type"              => "text",
                "text"              => [
                    "preview_url" => false,
                    "body"        => $text,
                ],
            ]
        );

        // 2) append to JSON history
        $data = $conversation->data ?? [];
        $data['history'][] = [
            'step'      => 'manual_reply',
            'text'      => $text,
            'time'      => now()->toIso8601String(),
            'direction' => 'out',
        ];
        $conversation->data = $data;
        $conversation->touch(); // updates updated_at
        $conversation->save();

        return response()->json(['status' => 'ok']);
    }
}

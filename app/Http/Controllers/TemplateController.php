<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class TemplateController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $settings = Setting::where('user_id', $user->id)->first();

        if (!$settings || !$settings->business_account_id || !$settings->access_token) {
            return redirect()
                ->route('templates') 
                ->with('error', 'Please configure WhatsApp API credentials in Settings first.');
        }

        $wabaId      = $settings->business_account_id;
        $accessToken = $settings->access_token;

        $response = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v20.0/{$wabaId}/message_templates", [
                'limit' => 200,
            ]);

        if (!$response->ok()) {
            return redirect()
                ->route('templates')
                ->with('error', 'Failed to fetch templates from Meta.');
        }

        $meta_templates = $response->json('data') ?? [];
        return view('templates.index', ['meta_templates' => $meta_templates]);
    }

}

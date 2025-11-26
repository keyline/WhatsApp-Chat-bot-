<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\BotWebhookController;
// use App\Http\Middleware\VerifyCsrfToken;

// 🔐 Guest routes (only for not-logged-in users)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 🔐 Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 🔒 Protected routes (needs login)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts');
    Route::post('/contacts/store', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('/bots-flows', [BotController::class, 'index'])->name('bots_flows');
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/api/save', [SettingsController::class, 'saveApi'])->name('settings.api.save');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/send', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages/send', [MessageController::class, 'store'])->name('messages.store');

    // Bot flow url & token settings
    Route::get('/bot-settings', [BotController::class, 'dashboard'])->name('bot.settings.dashboard');
    Route::post('/bot/send-message', [BotController::class, 'sendManualMessage'])
    ->name('bot.sendMessage');
      // full inbox page
    Route::get('/bot/inbox', [ChatInboxController::class, 'index'])
        ->name('bot.inbox');

    // fetch history for one conversation (AJAX)
    Route::get('/bot/inbox/{conversation}', [ChatInboxController::class, 'history'])
        ->name('bot.inbox.history');

    // send manual message (AJAX)
    Route::post('/bot/inbox/{conversation}/send', [ChatInboxController::class, 'send'])
        ->name('bot.inbox.send');
});

// Default home redirect
Route::get('/', fn () => redirect()->route('dashboard'));

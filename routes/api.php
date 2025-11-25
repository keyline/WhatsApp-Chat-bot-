<?php
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\BotWebhookController;

// Route::match(['get', 'post'], '/webhook/bot/{token}', [BotWebhookController::class, 'handle'])
//     ->name('bot.webhook');

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotWebhookController;

Route::match(['get', 'post'], '/webhook/bot', [BotWebhookController::class, 'handle']);

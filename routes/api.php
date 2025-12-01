<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotWebhookControllerTwo;

Route::match(['get', 'post'], '/webhook/bot', [BotWebhookControllerTwo::class, 'handle']);


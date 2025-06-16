<?php

use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

$token = env('TELEGRAM_BOT_TOKEN');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("/bot/getupdates/$token", [WebhookController::class, 'index'])->name('webhook');

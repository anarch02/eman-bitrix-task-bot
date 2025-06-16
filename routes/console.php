<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('set-webhook', function(){
    $response = Telegram::setWebhook(['url' => route('webhook')]);

    if($response == 1)
    {
        $this->info('Вебхук установлен!');
    }
})->purpose('Set the Telegram webhook');

Artisan::command('delete-webhook', function(){
    $response = Telegram::removeWebhook();

    $this->info($response);
})->purpose('Delete the Telegram webhook');

Artisan::command('test', function(){
    $this->info(route('bitrix.webhook'));
})->purpose('Delete the Telegram webhook');

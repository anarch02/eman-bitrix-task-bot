<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['join'];
    protected string $description = 'Команда для начала работы с ботом';

    public function handle()
    {
        $chat = $this->getUpdate()->getMessage()->chat;
        $chatId = $chat->id;

        // Отправляем сообщение с клавиатурой
        $this->replyWithMessage([
            'chat_id' => $chatId,
            'text' => "👋 Добро пожаловать в бот компании *Eman*!\n\nВаш Telegram ID: `$chatId`\n\n📝 Пожалуйста, скопируйте этот ID и вставьте его в свой профиль Битрикс.",
            'parse_mode' => 'Markdown',
        ]);
    }
}

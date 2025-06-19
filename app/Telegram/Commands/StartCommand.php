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

        // Inline кнопка "Проверить"
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔍 Проверить', 'callback_data' => 'verify_user'],
                ]
            ]
        ];

        $this->replyWithMessage([
            'chat_id' => $chatId,
            'text' => "👋 Добро пожаловать в бот компании *Eman*!\n\nВаш Telegram ID: `$chatId`\n\n📝 Пожалуйста, скопируйте этот ID и вставьте его в свой профиль Битрикс.\n\nКогда будете готовы — нажмите *Проверить*.",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard),
        ]);
    }
}

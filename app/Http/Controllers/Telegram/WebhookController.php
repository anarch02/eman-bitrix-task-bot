<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function index()
    {
        Telegram::commandsHandler(true);

        $update = Telegram::getWebhookUpdate();
        $updateArray = $update->toArray();

        if (isset($updateArray['my_chat_member'])) {
            $this->handleMyChatMember($updateArray['my_chat_member']);
        }

        return response('ok');
    }

    /**
     * Обработка события my_chat_member
     */
    private function handleMyChatMember(array $chatMemberUpdate): void
    {
        $chatData = $chatMemberUpdate['chat'] ?? null;

        if ($chatData) {
            $chat = $this->saveOrUpdateChat($chatData);
            Log::info("Updated membership info for chat_id: {$chat->chat_id}");
        }
    }


    /**
     * Универсальное сохранение или обновление информации о чате
     */
    private function saveOrUpdateChat(array $chatData): Chat
    {
        return Chat::updateOrCreate(
            ['chat_id' => $chatData['id']],
            [
                'type' => $chatData['type'] ?? null,
                'title' => $chatData['title'] ?? null,
                'username' => $chatData['username'] ?? null,
            ]
        );
    }
}

<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\BitrixUser;
use App\Models\Chat;
use App\Services\BitrixService;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function index()
    {
        $update = Telegram::getWebhookUpdate();
        $updateArray = $update->toArray();

        Telegram::commandsHandler(true);

        if (isset($updateArray['message']['chat'])) {
            $chat = $updateArray['message']['chat'];

            Chat::updateOrCreate(
                ['chat_id' => $chat['id']],
                [
                    'first_name' => $chat['first_name'] ?? null,
                    'last_name' => $chat['last_name'] ?? null,
                    'username' => $chat['username'] ?? null,
                ]
            );
        }

        if (isset($updateArray['callback_query'])) {
            $this->handleCallbackQuery($updateArray['callback_query']);
        }

        return response('ok');
    }

    /**
     * Обработка callback_query от inline-кнопок
     */
    private function handleCallbackQuery(array $callback)
    {
        $data = $callback['data'];
        $chat = $callback['message']['chat'];
        $chatId = $chat['id'];

        if ($data === 'verify_user') {
            BitrixService::importUsers();
            $user = BitrixUser::where('telegram_id', $chatId)->first();

            if ($user) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "✅ Вы успешно связаны с пользователем *{$user->name}* в Битрикс!",
                    'parse_mode' => 'Markdown',
                ]);
            } else {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ Telegram ID не найден в Битрикс. Пожалуйста, убедитесь, что вы указали его в своём профиле и попробуйте ещё раз.",
                ]);
            }

            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback['id'],
                'text' => 'Проверка выполнена',
                'show_alert' => false,
            ]);
        }
    }
}

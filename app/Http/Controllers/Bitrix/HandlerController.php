<?php

namespace App\Http\Controllers\Bitrix;

use App\Http\Controllers\Controller;
use App\Models\BitrixUser;
use App\Services\BitrixService;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class HandlerController extends Controller
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('services.telegram.bot_token'));
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        $event = $payload['event'] ?? null;

        if (!$event) {
            return response()->json(['error' => 'Событие не указано'], 400);
        }

        return match ($event) {
            'ONTASKADD', 'ONTASKUPDATE' => $this->handleTaskCreatedOrUpdated($payload),
            'ONTASKCOMMENTADD' => $this->handleTaskCommented($payload),
            default => $this->handleUnknownEvent($event),
        };
    }

    protected function handleTaskCreatedOrUpdated(array $payload)
    {
        $task = BitrixService::handleTaskAddWebhook($payload);
        if (!$task) {
            return response()->json(['error' => 'Задача не найдена'], 404);
        }

        $event = $payload['event'] ?? null;

        if ($event === 'ONTASKADD') {
            $this->sendToResponsible($task);
            $this->sendToAuditors($task);
        }

        if ($event === 'ONTASKUPDATE') {
            $textForResponsible = "✏️ *Задача обновлена!*\n\n"
                . "*Задача:* {$task['title']}\n"
                . "*Описание:* {$task['description']}\n"
                . "*Дедлайн:* " . $this->formatDeadline($task['deadline']) . "\n"
                . "_Задача №{$task['id']}_";

            $textForAuditors = "✏️ *Обновление задачи, за которой вы наблюдаете!*\n\n"
                . "*Задача:* {$task['title']}\n"
                . "*Описание:* {$task['description']}\n"
                . "*Дедлайн:* " . $this->formatDeadline($task['deadline']) . "\n"
                . "*Исполнитель:* {$task['responsible_name']} {$task['responsible_last_name']}\n"
                . "_Задача №{$task['id']}_";

            $this->sendToResponsible($task, $textForResponsible);
            $this->sendToAuditors($task, $textForAuditors);
        }

        return response()->json(['message' => 'Задача обработана']);
    }


    protected function handleTaskCommented(array $payload)
    {
        $taskId = $payload['data']['FIELDS_AFTER']['TASK_ID'] ?? null;
        $commentId = $payload['data']['FIELDS_AFTER']['ID'] ?? null;

        $task = BitrixService::getTask((int)$taskId);
        if (!$task) {
            return response()->json(['error' => 'Задача не найдена'], 404);
        }

        $commentText = BitrixService::getTaskComment((int)$commentId) ?? '[Комментарий недоступен]';

        $text = "💬 Добавлен *комментарий* к задаче\n\n"
            . "*{$task['title']}*\n"
            . "_Задача №{$task['id']}_\n\n"
            . "*Комментарий:*\n"
            . "{$commentText}";

        $this->sendToResponsible($task, $text);
        $this->sendToAuditors($task, $text);

        return response()->json(['message' => 'Комментарий обработан']);
    }


    protected function handleUnknownEvent(string $event)
    {
        return response()->json(['message' => 'Событие не обрабатывается'], 200);
    }

    private function formatDeadline(?string $deadline): string
    {
        if (!$deadline || $deadline === 'Дедлайн не указан') {
            return 'Не указан';
        }

        $date = \Carbon\Carbon::parse($deadline);
        return $date->locale('ru')->translatedFormat('F d, l H:i'); // Месяц день, День недели Время
    }

    private function sendToResponsible(array $task, ?string $customText = null): void
    {
        $responsible = BitrixUser::where('bitrix_id', $task['responsible_id'])->first();
        if (!$responsible || !$responsible->telegram_id) {
            return;
        }

        $deadlineFormatted = $this->formatDeadline($task['deadline']);

        // Получение имён наблюдателей
        $auditorNames = collect($task['auditors'])->map(function ($id) {
            return BitrixUser::where('bitrix_id', $id)->first()?->name;
        })->filter()->join(', ') ?: 'Нет';

        $text = $customText ?? (
            "✅ *Вам поставлена задача!*\n\n"
            . "*Задача:* {$task['title']}\n"
            . "*Описание:* {$task['description']}\n"
            . "*Дедлайн:* {$deadlineFormatted}\n"
            . "*Наблюдатели:* {$auditorNames}\n"
            . "_Задача №{$task['id']}_"
        );

        $this->telegram->sendMessage([
            'chat_id' => $responsible->telegram_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function sendToAuditors(array $task, ?string $customText = null): void
    {
        $deadlineFormatted = $this->formatDeadline($task['deadline']);

        $text = $customText ?? (
            "👀 *Вас назначили наблюдателем к задаче!*\n\n"
            . "*Задача:* {$task['title']}\n"
            . "*Описание:* {$task['description']}\n"
            . "*Дедлайн:* {$deadlineFormatted}\n"
            . "*Исполнитель:* {$task['responsible_name']} {$task['responsible_last_name']}\n"
            . "_Задача №{$task['id']}_"
        );

        foreach ($task['auditors'] as $auditorId) {
            $auditor = BitrixUser::where('bitrix_id', $auditorId)->first();
            if ($auditor && $auditor->telegram_id) {
                $this->telegram->sendMessage([
                    'chat_id' => $auditor->telegram_id,
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                ]);
            }
        }
    }
}

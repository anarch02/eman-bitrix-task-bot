<?php

namespace App\Services;

use App\Models\BitrixUser;
use Illuminate\Support\Facades\Http;

class BitrixService
{
    protected static string $webhookUrl;

    protected static function init(): void
    {
        if (!isset(self::$webhookUrl)) {
            self::$webhookUrl = config('services.bitrix.webhook_url');
        }
    }

    protected static function request(string $method, array $params = []): ?array
    {
        self::init();

        $url = rtrim(self::$webhookUrl, '/') . '/' . ltrim($method, '/');

        $response = Http::post($url, $params);

        return $response->json('result');
    }

    public static function importUsers(): int
    {
        $result = self::request('user.get', [
            'filter' => ['!UF_TELEGRAM_ID' => false],
            'select' => ['ID', 'NAME', 'UF_USR_1750247680866']
        ]);

        if (!$result) {
            return 0;
        }

        $usersForDb = collect($result)->map(function ($user) {
            return [
                'bitrix_id'   => $user['ID'],
                'name'        => $user['NAME'],
                'telegram_id' => $user['UF_USR_1750247680866'] ?? null,
            ];
        })->toArray();

        foreach ($usersForDb as $userData) {
            BitrixUser::updateOrCreate(
                ['bitrix_id' => $userData['bitrix_id']],
                $userData
            );
        }

        return count($usersForDb);
    }

    public static function getTask(int $taskId): ?array
    {
        $task = self::request('task.item.getdata.json', ['id' => $taskId]);

        if (!$task) {
            return null;
        }

        return [
            'id' => $taskId,
            'title' => $task['TITLE'] ?? 'Без названия',
            'description' => $task['DESCRIPTION'] ?? 'Без описания',
            'responsible_id' => $task['RESPONSIBLE_ID'] ?? 'Не назначен',
            'deadline' => $task['DEADLINE'] ?? 'Дедлайн не указан',
            'auditors' => is_array($task['AUDITORS']) ? $task['AUDITORS'] : [],
            'created_by_name' => $task['CREATED_BY_NAME'] ?? "не указан",
            'responsible_name' => $task['RESPONSIBLE_NAME'] ?? "Имя не указано",
            'responsible_last_name' => $task['RESPONSIBLE_LAST_NAME'] ?? "Фамилия не указана",
        ];
    }

    public static function getTaskComment(int $commentId): ?string
    {
        $result = self::request('task.commentitem.get', ['commentId' => $commentId]);

        return strip_tags($result['POST_MESSAGE']);
    }


    /**
     * Обработка вебхука события ONTASKADD
     */
    public static function handleTaskAddWebhook(array $payload): ?array
    {
        $taskId = $payload['data']['FIELDS_AFTER']['ID'] ?? null;

        return self::getTask((int) $taskId);
    }
}

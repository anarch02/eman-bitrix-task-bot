<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixService
{
    protected string $webhookUrl;

    public function __construct()
    {
        // Можно хранить URL в .env
        $this->webhookUrl = config('services.bitrix.webhook_url');
    }

    /**
     * Базовый метод запроса к REST API
     */
    public function request(string $method, array $params = [])
    {
        $url = rtrim($this->webhookUrl, '/') . '/' . $method;

        $response = Http::get($url, $params);

        if (!$response->ok()) {
            Log::error("Bitrix API Error", [
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Получить список пользователей с заполненным Telegram ID
     */
    public function getUsersWithTelegramId(): array
    {
        $result = $this->request('user.get', [
            'filter' => ['!UF_TELEGRAM_ID' => false],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_TELEGRAM_ID']
        ]);

        return $result['result'] ?? [];
    }

    /**
     * Получить одного пользователя по ID
     */
    public function getUserById(int $id): ?array
    {
        $result = $this->request('user.get', [
            'filter' => ['ID' => $id],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_TELEGRAM_ID']
        ]);

        return $result['result'][0] ?? null;
    }
}

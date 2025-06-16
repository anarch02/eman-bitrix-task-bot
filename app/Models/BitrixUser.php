<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BitrixUser extends Model
{
    protected $fillable = [
        'bitrix_id',
        'name',
        'telegram_id',
    ];

    /**
     * Один пользователь имеет один чат, связь по telegram_id = chat_id
     */
    public function chat(): HasOne
    {
        return $this->hasOne(Chat::class, 'chat_id', 'telegram_id');
    }
}

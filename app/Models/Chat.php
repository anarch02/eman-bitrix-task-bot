<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    protected $fillable = [
        'chat_id',
        'username',
        'first_name',
        'last_name',
    ];

    /**
     * Один чат принадлежит одному Bitrix-пользователю, связь по chat_id = telegram_id
     */
    public function bitrixUser(): BelongsTo
    {
        return $this->belongsTo(BitrixUser::class, 'chat_id', 'telegram_id');
    }
}

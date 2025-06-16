<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
    ];
}

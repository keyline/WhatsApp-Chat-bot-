<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotOption extends Model
{
    protected $fillable = [
        'bot_question_id',
        'match_value',
        'next_key',
        'set_service',
        'store_field',
        'store_value',
        'is_default',
    ];

    public function question()
    {
        return $this->belongsTo(BotQuestion::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    use HasFactory;

    protected $table = 'bot_settings';

    protected $fillable = [
        'user_id',
        'bot_name',
        'trigger_type',
        'trigger_keyword',
        'flow_json',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

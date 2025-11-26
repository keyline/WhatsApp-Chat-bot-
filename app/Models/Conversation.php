<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'step',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}

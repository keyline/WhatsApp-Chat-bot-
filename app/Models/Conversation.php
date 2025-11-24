<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'step',
        'service',
        'option1',
        'option2',
        'name',
        'business_name',
        'contact_number',
        'email',
    ];
}

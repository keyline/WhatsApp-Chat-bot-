<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'user_id',
        'business_account_id',
        'phone_number_id',
        'whatsapp_number',
        'access_token',
        'app_secret',
        'verify_token',
        'webhook_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

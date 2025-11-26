<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $casts = [
    'scheduled_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'template_name',
        'whatsapp_numbers',
        'type',
        'status',
        'scheduled_at',
        'total_sent',
        'total_failed',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function targets()
    {
        return $this->hasMany(CampaignTarget::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $table = 'templates';

    protected $fillable = [
        'user_id',
        'template_name',
        'category',
        'language',
        'header',
        'body',
        'footer',
        'buttons',
        'status',
        'last_used_at',
    ];

    protected $casts = [
        'buttons'     => 'array',
        'last_used_at'=> 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

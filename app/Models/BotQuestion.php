<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotQuestion extends Model
{
    protected $fillable = ['key', 'service', 'message', 'store_field'];

    public function options()
    {
        return $this->hasMany(BotOption::class);
    }
}

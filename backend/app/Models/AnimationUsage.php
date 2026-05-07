<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimationUsage extends Model
{
    protected $fillable = ['animation_type', 'token', 'ip_address', 'city', 'country', 'created_at'];
    public $timestamps = false;
    protected $casts = [
        'created_at' => 'datetime',
    ];
}

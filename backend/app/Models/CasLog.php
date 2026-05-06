<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['timestamp', 'command', 'status', 'error'];
}

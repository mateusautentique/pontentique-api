<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClockEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'timestamp'
    ];
}

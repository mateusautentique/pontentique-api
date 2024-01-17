<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_id',
        'type',
        'status',
        'justification',
        'requested_data',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}

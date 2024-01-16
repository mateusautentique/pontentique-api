<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClockEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'timestamp',
        'justification',
        'dayOff',
        'doctor',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    protected $dates = ['timestamp'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

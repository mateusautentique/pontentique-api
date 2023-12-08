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
        'justification'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

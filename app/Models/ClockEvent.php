<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClockEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'timestamp',
        'justification',
        'day_off',
        'doctor',
        'controlId',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    protected $dates = ['timestamp', 'deleted_at'];

    protected $hidden = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

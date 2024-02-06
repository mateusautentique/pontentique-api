<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\AFDRegistryService;

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

    protected static function booted()
    {
        $service = new AFDRegistryService();

        static::created(function ($clockEvent) use ($service) {
            $registry = $service->generateClockRegistryLine($clockEvent, 'create');
            $service->sendRegistryLine($registry);
        });

        static::updated(function ($clockEvent) use ($service) {
            $registry = $service->generateClockRegistryLine($clockEvent, 'update');
            $service->sendRegistryLine($registry);
        });

        static::deleted(function ($clockEvent) use ($service) {
            $registry = $service->generateClockRegistryLine($clockEvent, 'delete');
            $service->sendRegistryLine($registry);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\LogRegistryService;

class ClockEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'timestamp',
        'justification',
        'day_off',
        'doctor',
        'control_id',
        'rh_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    protected $dates = ['timestamp', 'deleted_at'];

    protected $hidden = ['deleted_at'];

    protected static function booted()
    {
        $service = new LogRegistryService();

        static::created(function ($clockEvent) use ($service) {
            $registry = $service->generateClockLogLine($clockEvent, 'create');
            $service->sendLogs($registry);
        });

        static::updated(function ($clockEvent) use ($service) {
            $registry = $service->generateClockLogLine($clockEvent, 'update');
            $service->sendLogs($registry);
        });

        static::deleted(function ($clockEvent) use ($service) {
            $registry = $service->generateClockLogLine($clockEvent, 'delete');
            $service->sendLogs($registry);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

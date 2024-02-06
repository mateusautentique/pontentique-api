<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\AFDRegistryService;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'role',
        'work_journey_hours'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at'
    ];

    protected $casts = [
        'role' => 'string',
        'password' => 'hashed',
    ];

    protected $dates = ['timestamp', 'deleted_at'];

    protected static function booted()
    {
        $service = new AFDRegistryService();

        static::created(function ($clockEvent) use ($service) {
            $registry = $service->generateUserRegistryLine($clockEvent, 'create');
            $service->sendRegistryLine($registry);
        });

        static::updated(function ($clockEvent) use ($service) {
            $registry = $service->generateUserRegistryLine($clockEvent, 'update');
            $service->sendRegistryLine($registry);
        });

        static::deleted(function ($clockEvent) use ($service) {
            $registry = $service->generateUserRegistryLine($clockEvent, 'delete');
            $service->sendRegistryLine($registry);
        });
    }

    public function clockEvents()
    {
        return $this->hasMany(ClockEvent::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}

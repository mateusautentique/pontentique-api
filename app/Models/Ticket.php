<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_events_id',
        'type',
        'status',
        'justification',
        'requested_data',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function approve()
    {
        $requested_data = json_decode($this->requested_data, true);

        switch ($this->type) {
            case 'create':
                $this->clockEvent()->create($requested_data);
                break;
            case 'update':
                $this->clockEvent()->update($requested_data);
                break;
            case 'delete':
                $this->clockEvent()->delete();
                break;
        }
        $this->update(['status' => 'approved']);
    }

    public function deny()
    {
        $this->update(['status' => 'denied']);
    }

    public function clockEvent()
    {
        return $this->belongsTo(ClockEvent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

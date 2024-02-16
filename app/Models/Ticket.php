<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_event_id',
        'type',
        'status',
        'justification',
        'requested_data',
        'handled_by',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'requested_data' => 'array',
    ];

    protected $appends = ['user_name', 'clock_event_timestamp'];

    public function approve(int $admin_id)
    {
        $requested_data = json_decode($this->requested_data, true);

        switch ($this->type) {
            case 'create':
                $this->clockEvent()->create($requested_data);
                break;
            case 'update':
                if (!ClockEvent::where('id', $this->clock_event_id)->where('user_id', $this->user_id)->exists()) {
                    throw new \Exception('Esse registro não existe');
                }
                $this->clockEvent()->update($requested_data);
                break;
            case 'delete':
                if (!ClockEvent::where('id', $this->clock_event_id)->where('user_id', $this->user_id)->exists()) {
                    throw new \Exception('Esse registro não existe');
                }
                if ($this->clockEvent) {
                    $this->clockEvent->justification = $this->justification;
                    $this->clockEvent->save();
                    $this->clockEvent->delete();
                }
                break;
        }
        $this->update(['status' => 'approved', 'handled_by' => $admin_id]);
    }

    public function deny(int $admin_id)
    {
        $this->update(['status' => 'rejected', 'handled_by' => $admin_id]);
    }

    public function clockEvent()
    {
        return $this->belongsTo(ClockEvent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : null;
    }

    public function getClockEventTimestampAttribute()
    {
        return $this->clockEvent ? $this->clockEvent->timestamp->format('Y-m-d H:i:s') : null;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['requested_data'] = json_decode($array['requested_data'], true);
        unset($array['user']);
        unset($array['clock_event']);
        return $array;
    }
}

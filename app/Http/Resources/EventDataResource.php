<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventDataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'justification' => $this->justification,
            'type' => $this->index % 2 == 0 ? 'clock_in' : 'clock_out',
            'day_off' => $this->day_off,
            'doctor' => $this->doctor,
            'controlId' => $this->control_id,
        ];
    }
}

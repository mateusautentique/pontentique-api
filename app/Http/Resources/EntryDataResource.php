<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntryDataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'day' => $this->resource['day'],
            'expected_work_hours_on_day' => $this->resource['expected_work_hours_on_day'],
            'normal_hours_worked_on_day' => $this->resource['normal_hours_worked_on_day'],
            'extra_hours_worked_on_day' => $this->resource['extra_hours_worked_on_day'],
            'balance_hours_on_day' => $this->resource['balance_hours_on_day'],
            'total_time_worked_in_seconds' => $this->resource['total_time_worked_in_seconds'],
            'event_count' => $this->resource['event_count'],
            'events' => $this->resource['events'],
        ];
    }
}

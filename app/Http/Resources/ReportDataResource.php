<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportDataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->resource['user_id'],
            'user_name' => $this->resource['user_name'],
            'total_expected_hours' => $this->resource['total_expected_hours'],
            'total_hours_worked' => $this->resource['total_hours_worked'],
            'total_normal_hours_worked' => $this->resource['total_normal_hours_worked'],
            'total_hour_balance' => $this->resource['total_hour_balance'],
            'entries' => $this->resource['entries'],
        ];
    }
}

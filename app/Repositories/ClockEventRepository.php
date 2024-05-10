<?php

namespace App\Repositories;

use App\Models\ClockEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClockEventRepository
{
    /**
     *  Retrives all clock records for given user id.
     */
    public function findByUserId(int $userId): Collection
    {
        return ClockEvent::where('user_id', $userId)->get();
    }

    /**
     *  Retrieves all clock events for a given user between two dates.
     */
    public function getClockEventsByDate(Carbon $startDate, Carbon $endDate, User $user): Collection
    {
        $clockEvents = ClockEvent::where('user_id', $user->id)
            ->with('user')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->get();

        return $clockEvents->groupBy(function ($event) {
            return $event->timestamp->format('Y-m-d');
        });
    }
}

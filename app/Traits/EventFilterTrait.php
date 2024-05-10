<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;

trait EventFilterTrait
{
    /*
     * The EventFilterTrait is intented to be used on an object of clock events.
     * Usually, on a result of a ClockEvent query.
     * @see App\Repositories\ClockEventRepository
     */
    private function separateEvents(object $events): array
    {
        $normalEvents = $this->filterEvents($events, false);
        $dayOffEvents = $this->filterEvents($events, true);

        return [$normalEvents, $dayOffEvents];
    }

    private function filterEvents(object $events, bool $isDayOff): object
    {
        return $events->filter(function ($event) use ($isDayOff) {
            $isEventDayOff = (bool)$event['day_off'];
            $isEventDoctor = (bool)$event['doctor'];
            return $isDayOff ? ($isEventDayOff || $isEventDoctor) : (!$isEventDayOff && !$isEventDoctor);
        })->values();
    }
}

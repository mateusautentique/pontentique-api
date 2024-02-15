<?php

namespace App\Services;

use App\Models\ClockEvent;

class AFDService
{
    public function generateACJEF()
    {
    }

    public function generateAFDT(): string
    {
        $nsrCounter = 1;
        $registry = "";

        $clockEvents = ClockEvent::withTrashed()->get();

        foreach ($clockEvents as $event) {
            if ($event->day_off || $event->doctor) {
                continue;
            }
            $nsr = str_pad($nsrCounter++, 9, '0', STR_PAD_LEFT);
            $type = '2';
            $timestamp = $event->timestamp->format('dmYHi');
            $pis = str_pad($event->user->pis, 11, '0', STR_PAD_LEFT);
            $registryNumber = $event->control_id ? "00014003750230599" : "00000000000000000";
            $eventType = $this->getClockEventType($event);
            $pairNumber = str_pad($this->getEventPairNumber($event), 2, '0', STR_PAD_LEFT);
            $registryType = $event->justification ? 'I' : 'O';
            $justification = $event->justification ? $event->justification : "";

            $registry .= "{$nsr}{$type}{$timestamp}{$pis}{$registryNumber}{$eventType}{$pairNumber}{$registryType}{$justification}\n";
        }
        $header = $this->generateHeader();
        $trailer = str_pad($nsrCounter, 9, '0', STR_PAD_LEFT) . "9";
        $afdt = $header . $registry . $trailer;

        return $afdt;
    }

    private function getClockEventType($event): string
    {
        if ($event->deleted_at) {
            return 'D';
        }
        $clockEventsOnDay = ClockEvent::whereDate('timestamp', $event->timestamp->format('Y-m-d'))->get();

        $filteredEvents = $clockEventsOnDay->filter(function ($clockEvent) use ($event) {
            return $clockEvent->user_id == $event->user_id;
        });

        $index = $filteredEvents->search(function ($clockEvent) use ($event) {
            return $clockEvent->id == $event->id;
        });

        return $index % 2 == 0 ? 'E' : 'S';
    }

    private function getEventPairNumber($event): int
    {
        $clockEventsOnDay = ClockEvent::whereDate('timestamp', $event->timestamp->format('Y-m-d'))->get();

        $filteredEvents = $clockEventsOnDay->filter(function ($clockEvent) use ($event) {
            return $clockEvent->user_id == $event->user_id;
        });

        $index = $filteredEvents->search(function ($clockEvent) use ($event) {
            return $clockEvent->id == $event->id;
        });

        return ceil(($index + 1) / 2);
    }

    private function generateHeader(): string
    {
        $nsr = "000000000";
        $type = "1";
        $identifier = "1";
        $cnpj = "29423653000165";
        $cei = "000000000000";
        $companyName = str_pad("AUTENTIQUE LTDA", 150, ' ', STR_PAD_RIGHT);

        $firstClockEvent = ClockEvent::orderBy('timestamp', 'asc')->first();
        $lastClockEvent = ClockEvent::orderBy('timestamp', 'desc')->first();

        $startDate = $firstClockEvent ? $firstClockEvent->timestamp->format('dmY') : date('dmY');
        $endDate = $lastClockEvent ? $lastClockEvent->timestamp->format('dmY') : date('dmY');
        $currentDate = date('dmYHi');

        $header = "{$nsr}{$type}{$identifier}{$cnpj}{$cei}{$companyName}{$startDate}{$endDate}{$currentDate}\n";

        return $header;
    }
}

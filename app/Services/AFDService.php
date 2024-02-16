<?php

namespace App\Services;

use App\Models\ClockEvent;
use App\Models\User;
use Carbon\Carbon;
use DateTime;

class AFDService
{
    public function generateACJEF(): string
    {
        $nsrCounter = 3;
        $registry = "";
        $header = $this->generateHeader();
        $contractHours = "000000002200010800120013001700\n";

        $users = User::all();

        foreach ($users as $user) {
            $events = ClockEvent::where('user_id', $user->id)
                ->orderBy('timestamp', 'asc')
                ->get();
        
            $days = $events->groupBy(function ($event) {
                return $event->timestamp->format('Y-m-d');
            });
            
            foreach ($days as $day => $entries) {
                $nsr = str_pad($nsrCounter++, 9, '0', STR_PAD_LEFT);
                $type = '3';
                $pis = str_pad($user->pis, 11, '0', STR_PAD_LEFT);
                $day = date('dmY', strtotime($day));

                $firstEntry = $entries->filter(function ($entry) {
                    return !$entry->day_off && !$entry->doctor;
                })->first();
                $firstEntryTime = $firstEntry ? $firstEntry->timestamp->format('Hi') : "0000";

                $hoursCode = "0001";
                list($balanceHours, $extraHours, $normalHours) = $this->calculateHours($entries);
                $dayHours = $this->formatTime($normalHours);
                $nightHours = $this->formatTime(0);

                $extraHoursDayPay = "5000";
                $extraHoursNightPay = "8000";

                $extraHours1 = "0000";
                $extraHours2 = "0000";
                $extraHours3 = "0000";
                $extraHours4 = "0000";

                $bufferDay = DateTime::createFromFormat('dmY', $day);
                if (Carbon::instance($bufferDay)->isWeekend()){
                    $extraHours3 = $this->formatTime($extraHours);
                } else {
                    $extraHours1 = $this->formatTime($extraHours);
                }

                $abstenceHoursInSec = $normalHours < 28800 ? 28800 - $normalHours : 0;
                $abstenceHours = $this->formatTime($abstenceHoursInSec);

                $compensationSign = "1";
                if ($balanceHours < 0) {
                    $balanceHours = abs($balanceHours);
                    $compensationSign = "2";
                }

                $registry .= "{$nsr}{$type}{$pis}{$day}{$firstEntryTime}{$hoursCode}";
                $registry .= "{$dayHours}{$nightHours}{$extraHours1}{$extraHoursDayPay}D";
                $registry .= "{$extraHours2}{$extraHoursNightPay}N{$extraHours3}{$extraHoursDayPay}D";
                $registry .= "{$extraHours4}{$extraHoursNightPay}D";
                $registry .= "{$abstenceHours}{$this->formatTime($balanceHours)}{$compensationSign}\n";
            }
        }

        $trailer = str_pad($nsrCounter, 9, '0', STR_PAD_LEFT) . "9";

        $acjef = $header . $contractHours . $registry . $trailer;
        return $acjef;
    }

    public function generateAFDT(): string
    {
        $nsrCounter = 2;
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
            $justification = $event->justification ? substr($event->justification, 0, 100) : "";

            $registry .= "{$nsr}{$type}{$timestamp}{$pis}{$registryNumber}{$eventType}{$pairNumber}{$registryType}{$justification}\n";
        }
        $header = $this->generateHeader();
        $trailer = str_pad($nsrCounter, 9, '0', STR_PAD_LEFT) . "9";
        $afdt = $header . $registry . $trailer;

        return $afdt;
    }

    //AUX
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
        $nsr = "000000001";
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

    private function calculateHours(object $entries): array
    {
        $totalTimeWorkedInSeconds = $this->calculateTotalTime($entries);
        return $this->calculateWorkHours($totalTimeWorkedInSeconds);
    }

    private function calculateTotalTime(object $events): int
    {
        $totalTime = 0;
    
        $events->each(function ($item, $index) use (&$totalTime, $events) {
            if ($index % 2 == 0) {
                $clockInEvent = $item;
                $clockOutEvent = $events->get($index + 1);
    
                if ($clockOutEvent) {
                    $clockInTime = \Carbon\Carbon::parse($clockInEvent['timestamp']);
                    $clockOutTime = \Carbon\Carbon::parse($clockOutEvent['timestamp']);
                    $timeWorked = $clockInTime->diffInSeconds($clockOutTime);
                    $timeWorked = max($timeWorked, 60);
                    $totalTime += $timeWorked;
                }
            }
        });
    
        return $totalTime;
    }
    
    private function calculateWorkHours(
        int $totalTimeWorked,
        float $defaultWorkJourneyHours = 8
    ): array {
        $expectedWorkHoursInSec = $defaultWorkJourneyHours * 3600;
        $normalHoursInSec = min($totalTimeWorked, $expectedWorkHoursInSec);
        $extraHoursInSec = max(0, $totalTimeWorked - $normalHoursInSec);
        $balanceHoursInSec = $totalTimeWorked - $expectedWorkHoursInSec;
    
        return [$balanceHoursInSec, $extraHoursInSec, $normalHoursInSec];
    }

    private function formatTime(int $timeInSeconds): string
    {
        $normalHoursInMinutes = intdiv($timeInSeconds, 60);
        $hours = str_pad(intdiv($normalHoursInMinutes, 60), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($normalHoursInMinutes % 60, 2, '0', STR_PAD_LEFT);
        return $hours . $minutes;
    }
}

<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HourCalculationTrait
{
    use TimeConversionTrait;

    /**
     * Calculate the total time worked in between events, returns time in seconds.
     */
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

    private function calculateWorkHours(int $totalTimeWorked, int $workJourneyHoursForDay, float $defaultWorkJourneyHours = 8): array
    {
        if ($totalTimeWorked >= 28200 && $totalTimeWorked <= 29400) {
            return [0, 28800];
        }

        $normalHoursInSec = min($totalTimeWorked, $workJourneyHoursForDay);
        $extraHoursInSec = max(0, $totalTimeWorked - $normalHoursInSec);
        if ($workJourneyHoursForDay < $defaultWorkJourneyHours) {
            $extraHoursInSec = 0;
        }
        return [$extraHoursInSec, $normalHoursInSec];
    }

    private function calculateTotalTimeAndNormalHours(Collection $clockEvents): array
    {
        $totalTimeWorkedInSeconds = $clockEvents->sum('total_time_worked_in_seconds');
        $totalNormalHours = $clockEvents->map(function ($clockEvent) {
            return $this->convertTimeToDecimal($clockEvent['normal_hours_worked_on_day']);
        })->sum();

        return [$totalTimeWorkedInSeconds, $totalNormalHours];
    }

    /**
     * Calculate the balance of hours between worked hours and expected work hours, return is a string.
     * Example: 8:30
     */
    private function calculateBalanceOfHours(int $workedHoursInSec, int $expectedWorkHoursInSec): string
    {
        $balanceOfHours = ($workedHoursInSec - $expectedWorkHoursInSec) / 3600;
        return $this->convertDecimalToTime($balanceOfHours);
    }
}

<?php

namespace App\Services;

use App\Http\Resources\EventDataResource;
use App\Http\Resources\EntryDataResource;
use App\Http\Resources\ReportDataResource;
use App\Models\ClockEvent;
use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClockService
{
    public function registerClock(int $id): string
    {
        $clockEvent = ClockEvent::create([
            'user_id' => $id,
            'timestamp' => Carbon::now(),
        ]);

        return 'Entrada registrada com sucesso em ' . $clockEvent->timestamp;
    }

    public function getClockReport(array $request): ReportDataResource
    {
        $user = User::find($request['user_id']);
        if (!$user) {
            throw new ModelNotFoundException;
        }

        $query = $this->generateQuery($request, $user);

        $clockEvents = $this->getClockEvents($query, $user->work_journey_hours * 3600);
        $clockEvents = $this->fillMissingDays($request, $clockEvents, $user->work_journey_hours);

        return $this->generateReport($clockEvents, $user);
    }

    public function setDayOffForDate(array $data): string
    {
        $start = Carbon::createFromFormat('Y-m-d', $data['start_date']);
        $end = Carbon::createFromFormat('Y-m-d', $data['end_date']);

        $start_time = Carbon::createFromFormat('H:i', $data['start_time']);
        $end_time = Carbon::createFromFormat('H:i', $data['end_time']);

        for ($date = $start; $date->lte($end); $date->addDay()) {
            ClockEvent::create([
                'user_id' => $data['user_id'],
                'timestamp' => $date->copy()->setTime($start_time->hour, $start_time->minute, 0)->format('Y-m-d H:i:s'),
                'justification' => $data['justification'],
                'day_off' => $data['day_off'],
                'doctor' => $data['doctor'],
            ]);

            ClockEvent::create([
                'user_id' => $data['user_id'],
                'timestamp' => $date->copy()->setTime($end_time->hour, $end_time->minute, 0)->format('Y-m-d H:i:s'),
                'justification' => $data['justification'],
                'day_off' => $data['day_off'],
                'doctor' => $data['doctor'],
            ]);
        }
        return 'Folga atualizada com sucesso para o período de ' . 
                $start->format('Y-m-d') . ' ' . $start_time->format('H:i') . 
                ' até ' . $end->format('Y-m-d') . ' ' . $end_time->format('H:i');
    }

    //CLOCK CRUD

    public function getAllUserClockEntries(int $id): array
    {
        return ClockEvent::where('user_id', $id)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }

    public function deleteClockEntry(int $clock_id): string
    {
        $clockEvent = ClockEvent::find($clock_id);
        if ($clockEvent) {
            $clockEvent->delete();
            return 'Entrada deletada com sucesso';
        }
    }

    public function insertClockEntry(array $data): string
    {
        $clockEvent = ClockEvent::create($data);
        return 'Entrada inserida com sucesso em ' . $clockEvent->timestamp;
    }

    public function updateClockEntry(array $data): string
    {
        $clockEvent = ClockEvent::find($data['id']);
        if ($clockEvent) {
            $clockEvent->update($data);
            return 'Entrada atualizada com sucesso';
        }
    }

    public function deleteAllClockEntries(): string
    {
        ClockEvent::truncate();
        return 'Todas as entradas foram deletadas com sucesso';
    }

    //HOUR CALCULATION

    private function calculateTotalTime($events)
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

    private function calculateWorkHours($totalTimeWorked, $workJourneyHoursForDay, $defaultWorkJourneyHours = 8)
    {
        $normalHoursInSec = min($totalTimeWorked, $workJourneyHoursForDay);
        $extraHoursInSec = max(0, $totalTimeWorked - $normalHoursInSec);
        if ($workJourneyHoursForDay < $defaultWorkJourneyHours) {
            $extraHoursInSec = 0;
        }
        return [$extraHoursInSec, $normalHoursInSec];
    }

    private function calculateTotalTimeAndNormalHours($clockEvents)
    {
        $totalTimeWorkedInSeconds = $clockEvents->sum('total_time_worked_in_seconds');
        $totalNormalHours = $clockEvents->map(function ($clockEvent) {
            return $this->convertTimeToDecimal($clockEvent['normal_hours_worked_on_day']);
        })->sum();

        return [$totalTimeWorkedInSeconds, $totalNormalHours];
    }

    private function calculateBalanceOfHours(int $workedHoursInSec, int $expectedWorkHoursInSec)
    {
        $balanceOfHours = ($workedHoursInSec - $expectedWorkHoursInSec) / 3600;
        return $this->convertDecimalToTime($balanceOfHours);
    }

    //UTILS

    //DATE FORMATTING

    private function generateQuery(array $timestamps, User $user)
    {
        $userCreatedDate = $user->created_at ?? Carbon::minValue();

        $startDate = $timestamps['start_date'] ?? $userCreatedDate;
        $endDate = $timestamps['end_date'] ?? Carbon::now();

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate)->endOfDay();

        return ClockEvent::where('user_id', $user->id)
                ->with('user')
                ->whereBetween('timestamp', [$startDate, $endDate]);
    }

    private function getDateRange($request)
    {
        $userId = $request['user_id'];
        $user = User::find($userId);
        $userCreatedDate = $user->created_at ?? Carbon::minValue();

        $startDate = $request['start_date'] ? Carbon::parse($request['start_date'])->startOfDay() : Carbon::parse($userCreatedDate)->startOfDay();
        $endDate = $request['end_date'] ? Carbon::parse($request['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        return collect(new DatePeriod($startDate, new DateInterval('P1D'), $endDate));
    }

    //CONVERSION

    private function convertDecimalToTime($hoursDecimal)
    {
        $sign = $hoursDecimal < 0 ? '-' : '';
        $hoursDecimal = abs($hoursDecimal);

        $hours = intval($hoursDecimal);
        $decimalHours = $hoursDecimal - $hours;
        $minutes = round($decimalHours * 60);

        if ($minutes == 60) {
            $hours += 1;
            $minutes = 0;
        }

        return sprintf("%s%d:%02d", $sign, $hours, $minutes);
    }

    private function convertTimeToDecimal($time)
    {
        [$hours, $minutes] = explode(':', $time);
        return $hours + ($minutes / 60);
    }

    //DATA FORMATTING

    private function groupClockEventsByDate($query)
    {
        return $query->orderBy('timestamp', 'asc')->get()
            ->groupBy(function ($event) {
                return $event->timestamp->format('Y-m-d');
            });
    }

    private function separateEvents($events)
    {
        $normalEvents = $this->filterEvents($events, false);
        $dayOffEvents = $this->filterEvents($events, true);

        return [$normalEvents, $dayOffEvents];
    }

    private function filterEvents($events, $isDayOff)
    {
        return $events->filter(function ($event) use ($isDayOff) {
            $isEventDayOff = (bool)$event['day_off'];
            $isEventDoctor = (bool)$event['doctor'];
            return $isDayOff ? ($isEventDayOff || $isEventDoctor) : (!$isEventDayOff && !$isEventDoctor);
        })->values();
    }

    private function fillMissingDays($request, $clockEvents, $userWorkJourneyHours)
    {
        $dateRange = $this->getDateRange($request);
        foreach ($dateRange as $date) {
            $date = Carbon::instance($date);
            $formattedDate = $date->format('Y-m-d');
            if (!(isset($clockEvents[$formattedDate]))) {
                $eventData = $this->createDefaultEntryResponse($formattedDate, $date->isWeekend(), $userWorkJourneyHours);
                $clockEvents->put($formattedDate, $eventData);
            }
        }
        $clockEvents = $clockEvents->sortKeys();
        return $clockEvents;
    }

    private function getClockEvents($query, $workJourneyHoursInSec)
    {
        return $this->groupClockEventsByDate($query)
            ->map(function ($eventsForDate) use ($workJourneyHoursInSec) {
                list($normalEvents, $dayOffEvents) = $this->separateEvents($eventsForDate);

                $normalEvents = $normalEvents->map(function ($event, $index) {
                    $event->index = $index;
                    return new EventDataResource($event);
                });
                
                $dayOffEvents = $dayOffEvents->map(function ($event, $index) {
                    $event->index = $index;
                    return new EventDataResource($event);
                });

                $day = $eventsForDate->first()->timestamp;
                $events = collect($normalEvents)->concat($dayOffEvents);
                $expectedWorkHoursOnDay = ($workJourneyHoursInSec - $this->calculateTotalTime($dayOffEvents));
                $totalTimeWorkedInSec = $this->calculateTotalTime($normalEvents);
                list($extraHoursInSec, $normalHoursInSec) = $this->calculateWorkHours($totalTimeWorkedInSec, $expectedWorkHoursOnDay);

                return $this->createEntryData(
                    $day,
                    $expectedWorkHoursOnDay,
                    $normalHoursInSec,
                    $extraHoursInSec,
                    $totalTimeWorkedInSec,
                    $eventsForDate,
                    $events
                );
            });
    }

    private function generateReport($clockEvents, $user)
    {
        list($totalTimeWorkedInSeconds, $totalNormalHours) = $this->calculateTotalTimeAndNormalHours($clockEvents);

        $expectedWorkJourneyHoursForPeriod = $clockEvents->map(function ($clockEvent) {
            return $this->convertTimeToDecimal($clockEvent['expected_work_hours_on_day']);
        })->sum();

        $totalHourBalance = $clockEvents->map(function ($clockEvent) {
            return $this->convertTimeToDecimal($clockEvent['balance_hours_on_day']);
        })->sum();

        return $this->createReportData(
            $user,
            $totalTimeWorkedInSeconds,
            $totalNormalHours,
            $expectedWorkJourneyHoursForPeriod,
            $totalHourBalance,
            $clockEvents
        );
    }

    //RESPONSE FORMATTING
    private function createEntryData($day, $expectedWorkHoursOnDay, $normalHoursInSec, $extraHoursInSec, $totalTimeWorkedInSec, $eventsForDate, $events)
    {
        return new EntryDataResource([
            'day' => $day->format('Y-m-d'),
            'expected_work_hours_on_day' => $this->convertDecimalToTime($expectedWorkHoursOnDay / 3600),
            'normal_hours_worked_on_day' => $this->convertDecimalToTime($normalHoursInSec / 3600),
            'extra_hours_worked_on_day' => $this->convertDecimalToTime($extraHoursInSec / 3600),
            'balance_hours_on_day' => $this->calculateBalanceOfHours(
                ($normalHoursInSec + $extraHoursInSec),
                $expectedWorkHoursOnDay
            ),
            'total_time_worked_in_seconds' => $totalTimeWorkedInSec,
            'event_count' => $eventsForDate->count(),
            'events' => $events,
        ]);
    }

    private function createDefaultEntryResponse($formattedDate, $isWeekend, $userWorkJourneyHours)
    {
        return new EntryDataResource([
            'day' => $formattedDate,
            'expected_work_hours_on_day' => $isWeekend ? '0:00' : $this->convertDecimalToTime($userWorkJourneyHours),
            'normal_hours_worked_on_day' => '0:00',
            'extra_hours_worked_on_day' => '0:00',
            'balance_hours_on_day' => $isWeekend ? '0:00' : $this->convertDecimalToTime(-$userWorkJourneyHours),
            'total_time_worked_in_seconds' => 0,
            'event_count' => 0,
            'events' => [],
        ]);
    }

    private function createReportData($user, $totalTimeWorkedInSeconds, $totalNormalHours, $expectedWorkJourneyHoursForPeriod, $totalHourBalance, $clockEvents)
    {
        return new ReportDataResource([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_expected_hours' => $this->convertDecimalToTime($expectedWorkJourneyHoursForPeriod),
            'total_hours_worked' =>  $this->convertDecimalToTime($totalTimeWorkedInSeconds / 3600),
            'total_normal_hours_worked' => $this->convertDecimalToTime($totalNormalHours),
            'total_hour_balance' => $this->convertDecimalToTime($totalHourBalance),
            'entries' => $clockEvents->values(),
        ]);
    }
}

<?php

namespace App\Services;

use App\Http\Requests\ClockReportRequest;
use App\Http\Resources\EventDataResource;
use App\Http\Resources\EntryDataResource;
use App\Http\Resources\ReportDataResource;
use App\Models\ClockEvent;
use App\Models\User;
use App\Repositories\ClockEventRepository;
use App\Traits\EventFilterTrait;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;

class ClockActionsService
{
    use EventFilterTrait;

    protected ClockEventRepository $clockEventRepository;
    protected User $user;

    public function __construct(ClockEventRepository $clockEventRepository, User $user)
    {
        $this->clockEventRepository = $clockEventRepository;
        $this->user = $user;
    }

    public function registerClock(int $id): string
    {
        $clockEvent = ClockEvent::create([
            'user_id' => $id,
            'timestamp' => Carbon::now(),
            'day_off' => false,
            'doctor' => false,
            'control_id' => false,
            'rh_id' => false,
        ]);

        return $clockEvent->timestamp;
    }

    public function getClockReport(ClockReportRequest $request): ReportDataResource
    {
        $user = User::find($request['user_id']);

        $startDateLimit = $user->created_at ?? Carbon::minValue();

        $startDate = Carbon::parse(!empty($timestamps['start_date']) ? $timestamps['start_date'] : $startDateLimit);
        $endDate = Carbon::parse(!empty($timestamps['end_date']) ? $timestamps['end_date'] : Carbon::now())->endOfDay();

        // $clockEventsObject = $this->clockEventRepository->getClockEventsByDate($startDate, $endDate, $this->user);

        $formattedClockEvents = $this->getClockEvents($startDate, $endDate, $user->work_journey_hours * 3600);
        $formattedClockEvents = $this->fillMissingDays($formattedClockEvents, $request, $user->work_journey_hours = 8);

        return $this->generateReport($formattedClockEvents, $user);
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

    //HOUR CALCULATION
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
        int $workJourneyHoursForDay,
        float $defaultWorkJourneyHours = 8
    ): array {
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

    private function calculateTotalTimeAndNormalHours(object $clockEvents): array
    {
        $totalTimeWorkedInSeconds = $clockEvents->sum('total_time_worked_in_seconds');
        $totalNormalHours = $clockEvents->map(function ($clockEvent) {
            return $this->convertTimeToDecimal($clockEvent['normal_hours_worked_on_day']);
        })->sum();

        return [$totalTimeWorkedInSeconds, $totalNormalHours];
    }

    private function calculateBalanceOfHours(int $workedHoursInSec, int $expectedWorkHoursInSec): string
    {
        $balanceOfHours = ($workedHoursInSec - $expectedWorkHoursInSec) / 3600;
        return $this->convertDecimalToTime($balanceOfHours);
    }

    //UTILS

    //DATE FORMATTING
    private function getDateRange(ClockReportRequest $request): object
    {
        $userId = $request['user_id'];
        $user = User::find($userId);
        $userCreatedDate = $user->created_at ?? Carbon::minValue();

        $startDate = !empty($request['start_date']) ? Carbon::parse($request['start_date'])->startOfDay() : Carbon::parse($userCreatedDate)->startOfDay();
        $endDate = !empty($request['end_date']) ? Carbon::parse($request['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        return collect(new DatePeriod($startDate, new DateInterval('P1D'), $endDate));
    }

    private function getClockEvents(Carbon $startDate, Carbon $endDate, int $workJourneyHoursInSec): object
    {
        return $this->clockEventRepository->getClockEventsByDate($startDate, $endDate, $this->user)
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

    //CONVERSION

    private function convertDecimalToTime(float $hoursDecimal): string
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

    private function convertTimeToDecimal(string $time): float
    {
        [$hours, $minutes] = explode(':', $time);
        return $hours + ($minutes / 60);
    }

    //DATA FORMATTING
    private function fillMissingDays(object $clockEvents, ClockReportRequest $request, int $userWorkJourneyHours): object
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

    private function generateReport(object $clockEvents, User $user): ReportDataResource
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
    private function createEntryData(
        object $day,
        int $expectedWorkHoursOnDay,
        int $normalHoursInSec,
        int $extraHoursInSec,
        int $totalTimeWorkedInSec,
        object $eventsForDate,
        object $events
    ): EntryDataResource {
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

    private function createDefaultEntryResponse(
        string $formattedDate,
        bool $isWeekend,
        int $userWorkJourneyHours
    ): EntryDataResource {
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

    private function createReportData(
        User $user,
        int $totalTimeWorkedInSeconds,
        float $totalNormalHours,
        int $expectedWorkJourneyHoursForPeriod,
        float $totalHourBalance,
        object $clockEvents
    ): ReportDataResource {
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

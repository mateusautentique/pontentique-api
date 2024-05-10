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
use App\Traits\HourCalculationTrait;
use App\Traits\TimeConversionTrait;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Collection;

class ClockActionsService
{
    use EventFilterTrait, HourCalculationTrait, TimeConversionTrait;

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

        $startDate = Carbon::parse( ! empty($timestamps['start_date']) ? $timestamps['start_date'] : $startDateLimit);
        $endDate = Carbon::parse( ! empty($timestamps['end_date']) ? $timestamps['end_date'] : Carbon::now())->endOfDay();

        $clockEventsObject = $this->clockEventRepository->getClockEventsByDate($startDate, $endDate, $this->user);

        $formattedClockEvents = $this->formatClockEventsIntoResource($clockEventsObject, $user->work_journey_hours * 3600);
        $formattedClockEvents = $this->fillMissingDays($formattedClockEvents, $request, $user->work_journey_hours);

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

    // AUX
    private function getDateRange(ClockReportRequest $request): Collection
    {
        $userId = $request['user_id'];
        $user = User::find($userId);
        $userCreatedDate = $user->created_at ?? Carbon::minValue();

        $startDate = !empty($request['start_date']) ? Carbon::parse($request['start_date'])->startOfDay() : Carbon::parse($userCreatedDate)->startOfDay();
        $endDate = !empty($request['end_date']) ? Carbon::parse($request['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        return collect(new DatePeriod($startDate, new DateInterval('P1D'), $endDate));
    }

    private function formatClockEventsIntoResource(Collection $clockEvents, int $workJourneyHoursInSec): Collection
    {
        return $clockEvents->map(function ($eventsForDate) use ($workJourneyHoursInSec) {
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

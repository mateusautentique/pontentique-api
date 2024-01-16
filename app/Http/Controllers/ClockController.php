<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ClockEvent;
use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;

class ClockController extends Controller
{
    // MAIN CLOCK LOGIC

    public function registerClock(Request $request)
    {
        try {
            $clockEvent = ClockEvent::create([
                'user_id' => $request->input('user_id'),
                'timestamp' => Carbon::now(),
            ]);

            return response()->json(['message' => 'Entrada registrada com sucesso em ' . $clockEvent->timestamp]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao registrar a entrada'], 500);
        }
    }

    public function getClockEventsByPeriod(Request $request)
    {
        try {
            $query = $this->validateDataIntoQuery($request);
            $workJourneyHours = $request['work_journey_hours'] ?? 8;

            $clockEvents = $this->groupClockEventsByDate($query)
                ->map(function ($eventsForDate) use ($workJourneyHours) {
                    $totalTimeWorked = $this->calculateTotalTimeWorked($eventsForDate);
                    $events = $eventsForDate->map(function ($event, $index) {
                        return $this->mapEvent($event, $index);
                    });

                    list($day, $workJourneyHoursForDay) = $this->calculateDay($eventsForDate, $workJourneyHours);
                    list($extraHoursInSec, $normalHoursInSec) = $this->calculateWorkHours($totalTimeWorked, $workJourneyHoursForDay);

                    return $this->createEventData(
                        $day,
                        $normalHoursInSec,
                        $extraHoursInSec,
                        $workJourneyHoursForDay,
                        $totalTimeWorked,
                        $eventsForDate,
                        $events
                    );
                });

            list($totalTimeWorkedInSeconds, $totalNormalHours) = $this->calculateTotalTimeAndNormalHours($clockEvents);

            $dateRange = $this->getDateRange($request);

            foreach ($dateRange as $date) {
                $date = Carbon::instance($date);
                $formattedDate = $date->format('Y-m-d');
                if (!(isset($clockEvents[$formattedDate]))) {
                    $eventData = $this->createDayData($formattedDate, $date->isWeekend());
                    $clockEvents->put($formattedDate, $eventData);
                }
            }

            $clockEvents = $clockEvents->sortKeys();
            $user = Auth::user();
            $weekdayClockEvents = $clockEvents->filter(function ($event, $date) {
                return !Carbon::parse($date)->isWeekend();
            });

            return response()->json($this->createReportData(
                $user,
                $totalTimeWorkedInSeconds,
                $totalNormalHours,
                $workJourneyHours,
                $weekdayClockEvents->count(),
                $clockEvents
            ));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    public function setDayOffForDay(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required',
                'day' => 'required|date_format:Y-m-d',
                'day_off' => 'required|boolean',
                'doctor' => 'required|boolean',
            ]);

            $userId = $validatedData['user_id'];
            $day = $validatedData['day'];
            $dayOff = $validatedData['day_off'];
            $doctor = $validatedData['doctor'];

            $clockEvents = ClockEvent::where('user_id', $userId)
                ->whereDate('timestamp', $day)
                ->get();

            foreach ($clockEvents as $clockEvent) {
                $clockEvent->update([
                    'day_off' => $dayOff,
                    'doctor' => $doctor,
                ]);
            }

            return response()->json(['message' => 'Folga atualizada com sucesso para o dia ' . $day]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Entrada inválida'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    //CLOCK CRUD

    public function getAllUserClockEntries(Request $request)
    {
        try {
            $clockEvents = ClockEvent::where('user_id', $request->user()->id)
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($clockEvents);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function deleteClockEntry(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required',
            ]);

            $clockEvent = ClockEvent::find($validatedData['id']);

            if ($clockEvent) {
                $clockEvent->delete();
                return response()->json(['message' => 'Entrada deletada com sucesso']);
            } else {
                return response()->json(['message' => 'Entrada não encontrada'], 404);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao atualizar a entrada'], 500);
        }
    }

    public function insertClockEntry(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required',
                'timestamp' => 'required',
                'justification' => 'required',
                'day_off' => 'required|boolean',
                'doctor' => 'required|boolean',
            ]);

            $clockEvent = ClockEvent::create($validatedData);

            return response()->json(['message' => 'Entrada inserida com sucesso em ' . $clockEvent['timestamp'] . ' com id ' . $clockEvent['id']]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao inserir a entrada'], 500);
        }
    }

    public function updateClockEntry(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required',
                'timestamp' => 'required',
                'justification' => 'required',
                'day_off' => 'required|boolean',
                'doctor' => 'required|boolean',
            ]);

            $clockEvent = ClockEvent::find($validatedData['id']);

            if ($clockEvent) {
                $clockEvent->update($validatedData);

                return response()->json(['message' => 'Entrada atualizada com sucesso para ' . $clockEvent['timestamp'] . ' com a justificativa: ' . $clockEvent['justification']]);
            } else {
                return response()->json(['message' => 'Entrada não encontrada'], 404);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao atualizar a entrada'], 500);
        }
    }

    public function deleteAllClockEntries()
    {
        ClockEvent::truncate();

        return response()->json(['message' => 'Todas as entradas foram deletadas com sucesso']);
    }

    //HOUR CALCULATION

    private function calculateTotalTimeWorked($eventsForDate)
    {
        $totalTimeWorked = 0;

        for ($i = 0, $count = count($eventsForDate); $i < $count; $i += 2) {
            $clockInEvent = $eventsForDate[$i];
            $clockOutEvent = $eventsForDate[$i + 1] ?? null;

            if ($clockOutEvent) {
                $timeWorked = $clockInEvent->timestamp->diffInSeconds($clockOutEvent->timestamp);
                $timeWorked = max($timeWorked, 60);
                $totalTimeWorked += $timeWorked;
            }
        }
        return $totalTimeWorked;
    }

    private function calculateWorkHours($totalTimeWorked, $workJourneyHoursForDay)
    {
        $workHoursInSeconds = $workJourneyHoursForDay * 3600;
        $extraHoursInSec = max(0, $totalTimeWorked - $workHoursInSeconds);
        $normalHoursInSec = $totalTimeWorked - $extraHoursInSec;
        return [$workHoursInSeconds, $extraHoursInSec, $normalHoursInSec];
    }

    private function calculateTotalTimeAndNormalHours($clockEvents)
    {
        $totalTimeWorkedInSeconds = $clockEvents->sum('total_time_worked_in_seconds');
        $totalNormalHours = $clockEvents->map(function ($clockEvent) {
            return $this->convertTimeToDecimal($clockEvent['normal_hours_worked_on_day']);
        })->sum();

        return [$totalTimeWorkedInSeconds, $totalNormalHours];
    }

    private function calculateBalanceOfHours($workJourneyHours, $totalTimeWorked, $daysWorked)
    {
        $balanceOfHours = $totalTimeWorked - ($workJourneyHours * $daysWorked);
        return $this->convertDecimalToTime($balanceOfHours);
    }

    //UTILS

    //DATA VALIDATION

    private function validateDataIntoQuery(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $userId = $validatedData['user_id'];
        $user = User::find($userId);
        $userCreatedDate = $user->created_at ?? Carbon::minValue();

        $startDate = $validatedData['start_date'] ?? $userCreatedDate;
        $endDate = $validatedData['end_date'] ?? Carbon::now();

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate)->endOfDay();

        return ClockEvent::where('user_id', $userId)
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
        $hours = intval($hoursDecimal);
        $decimalHours = abs($hoursDecimal - $hours);
        $minutes = round($decimalHours * 60);

        if ($minutes == 60) {
            $hours += 1;
            $minutes = 0;
        }

        return sprintf("%d:%02d", $hours, $minutes);
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

    private function mapEvent($event, $index)
    {
        return [
            'id' => $event->id,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
            'justification' => $event->justification,
            'type' => $index % 2 == 0 ? 'clock_in' : 'clock_out',
        ];
    }

    private function calculateDay($eventsForDate, $workJourneyHours)
    {
        $day = $eventsForDate->first()->timestamp;
        $isWeekend = $day->isWeekend();
        $workJourneyHoursForDay = $isWeekend ? 0 : $workJourneyHours;
        return [$day, $workJourneyHoursForDay];
    }

    //RESPONSE CREATION

    private function createEventData($day, $normalHoursInSec, $extraHoursInSec, $workJourneyHoursForDay, $totalTimeWorked, $eventsForDate, $events)
    {
        return [
            'day' => $day->format('Y-m-d'),
            'normal_hours_worked_on_day' => $this->convertDecimalToTime($normalHoursInSec / 3600),
            'extra_hours_worked_on_day' => $this->convertDecimalToTime($extraHoursInSec / 3600),
            'balance_hours_on_day' => $this->calculateBalanceOfHours(
                $workJourneyHoursForDay,
                $totalTimeWorked / 3600,
                1
            ),
            'total_time_worked_in_seconds' => $totalTimeWorked,
            'event_count' => $eventsForDate->count(),
            'events' => $events,
        ];
    }

    private function createDayData($formattedDate, $isWeekend)
    {
        $eventData = [
            'day' => $formattedDate,
            'normal_hours_worked_on_day' => '0:00',
            'extra_hours_worked_on_day' => '0:00',
            'balance_hours_on_day' => $isWeekend ? '0:00' : '-8:00',
            'total_time_worked_in_seconds' => 0,
            'event_count' => 0,
            'events' => [],
        ];

        return $eventData;
    }

    private function createReportData($user, $totalTimeWorkedInSeconds, $totalNormalHours, $workJourneyHours, $weekdayClockEventsCount, $clockEvents)
    {
        $totalHoursWorked = $this->convertDecimalToTime($totalTimeWorkedInSeconds / 3600);
        $totalNormalHoursWorked = $this->convertDecimalToTime($totalNormalHours);

        $responseData = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_hours_worked' => $totalHoursWorked,
            'total_normal_hours_worked' => $totalNormalHoursWorked,
            'total_hour_balance' => $this->calculateBalanceOfHours(
                $workJourneyHours,
                $totalTimeWorkedInSeconds / 3600,
                $weekdayClockEventsCount
            ),
            'entries' => $clockEvents->values(),
        ];

        return $responseData;
    }
}

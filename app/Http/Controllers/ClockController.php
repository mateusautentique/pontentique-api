<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ClockEvent;
use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;

/*
/   No começo, só eu e Deus sabiamos como funcionava esse código. Agora, só Deus sabe.
*/

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
            $data = $this->validateDataIntoQuery($request);
            $query = $data['query'];
            $user = $data['user'];
            $workJourneyHoursInSec = $user->work_journey_hours * 3600;

            $clockEvents = $this->getClockEvents($query, $workJourneyHoursInSec);

            $clockEvents = $this->fillMissingDays($request, $clockEvents, $user->work_journey_hours);

            return response()->json($this->generateReport($clockEvents, $user));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    public function setDayOffForDate(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required',
                'justification' => 'required',
                'start_date' => 'required|date_format:Y-m-d',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'required|date_format:Y-m-d',
                'end_time' => 'required|date_format:H:i',
                'day_off' => 'required|boolean',
                'doctor' => 'required|boolean',
            ]);
            
            $start = Carbon::createFromFormat('Y-m-d', $validatedData['start_date']);
            $end = Carbon::createFromFormat('Y-m-d', $validatedData['end_date']);
            
            $start_time = Carbon::createFromFormat('H:i', $validatedData['start_time']);
            $end_time = Carbon::createFromFormat('H:i', $validatedData['end_time']);
            
            for ($date = $start; $date->lte($end); $date->addDay()) {
                ClockEvent::create([
                    'user_id' => $validatedData['user_id'],
                    'timestamp' => $date->copy()->setTime($start_time->hour, $start_time->minute, 0)->format('Y-m-d H:i:s'),
                    'justification' => $validatedData['justification'],
                    'day_off' => $validatedData['day_off'],
                    'doctor' => $validatedData['doctor'],
                ]);
                
                ClockEvent::create([
                    'user_id' => $validatedData['user_id'],
                    'timestamp' => $date->copy()->setTime($end_time->hour, $end_time->minute, 0)->format('Y-m-d H:i:s'),
                    'justification' => $validatedData['justification'],
                    'day_off' => $validatedData['day_off'],
                    'doctor' => $validatedData['doctor'],
                ]);
            }
            return response()->json(['message' => 'Folga atualizada com sucesso para o período de ' . $start->format('Y-m-d') . ' ' . $start_time->format('H:i') . ' até ' . $end->format('Y-m-d') . ' ' . $end_time->format('H:i')]);
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
            $userId = $request->input('user_id');
    
            $clockEvents = ClockEvent::where('user_id', $userId)
                ->orderBy('id', 'desc')
                ->get();
    
            return response()->json($clockEvents);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function deleteClockEntry($clock_id)
    {
        try {
            $clockEvent = ClockEvent::find($clock_id);

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

        return [
            'query' => ClockEvent::where('user_id', $userId)
                ->with('user')
                ->whereBetween('timestamp', [$startDate, $endDate]),
            'user' => $user
        ];
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
                    return $this->createEventData($event, $index);
                });

                $dayOffEvents = $dayOffEvents->map(function ($event, $index) {
                    return $this->createEventData($event, $index);
                });
    
                $events = collect($normalEvents)->concat($dayOffEvents);

                $day = $eventsForDate->first()->timestamp;

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

        return $this->createReportData(
            $user,
            $totalTimeWorkedInSeconds,
            $totalNormalHours,
            $expectedWorkJourneyHoursForPeriod,
            $clockEvents
        );
    }

    //RESPONSE CREATION

    private function createEventData($event, $index)
    {
        return [
            'id' => $event->id,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
            'justification' => $event->justification,
            'type' => $index % 2 == 0 ? 'clock_in' : 'clock_out',
            'day_off' => $event->day_off,
            'doctor' => $event->doctor,
            'controlId' => $event->control_id,
        ];
    }

    private function createEntryData($day, $expectedWorkHoursOnDay, $normalHoursInSec, $extraHoursInSec, $totalTimeWorkedInSec, $eventsForDate, $events)
    {
        return [
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
        ];
    }

    private function createReportData($user, $totalTimeWorkedInSeconds, $totalNormalHours, $expectedWorkJourneyHoursForPeriod, $clockEvents)
    {
        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_expected_hours' => $this->convertDecimalToTime($expectedWorkJourneyHoursForPeriod),
            'total_hours_worked' =>  $this->convertDecimalToTime($totalTimeWorkedInSeconds / 3600),
            'total_normal_hours_worked' => $this->convertDecimalToTime($totalNormalHours),
            'total_hour_balance' => $this->calculateBalanceOfHours(
                $expectedWorkJourneyHoursForPeriod,
                $totalTimeWorkedInSeconds / 3600
            ),
            'entries' => $clockEvents->values(),
        ];
    }

    private function createDefaultEntryResponse($formattedDate, $isWeekend, $userWorkJourneyHours)
    {
        $eventData = [
            'day' => $formattedDate,
            'expected_work_hours_on_day' => $isWeekend ? '0:00' : $this->convertDecimalToTime($userWorkJourneyHours),
            'normal_hours_worked_on_day' => '0:00',
            'extra_hours_worked_on_day' => '0:00',
            'balance_hours_on_day' => $isWeekend ? '0:00' : $this->convertDecimalToTime(-$userWorkJourneyHours),
            'total_time_worked_in_seconds' => 0,
            'event_count' => 0,
            'events' => [],
        ];

        return $eventData;
    }
}

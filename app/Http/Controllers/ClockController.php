<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ClockEvent;
use Carbon\Carbon;

class ClockController extends Controller
{
    // MAIN CLOCK LOGIC

    public function registerClock(Request $request)
    {
        try {
            $clockEvent = ClockEvent::create([
                'user_id' => $request->input('user_id'),
                'timestamp' => now(),
            ]);

            return response()->json(['message' => 'Clock event registered successfully at ' . $clockEvent->timestamp]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Failed to register clock event'], 500);
        }
    }

    public function getClockEventsByPeriod(Request $request)
    {
        try {
            $query = $this->validateDataIntoQuery($request);

            $clockEvents = $query->orderBy('timestamp', 'asc')->get()
                ->groupBy(function ($event) {
                    return $event->timestamp->format('Y-m-d');
                })
                ->map(function ($eventsForDate) {
                    $totalTimeWorked = $this->calculateTotalTimeWorked($eventsForDate);
                    $events = $eventsForDate->map(function ($event, $index) {
                        return [
                            'id' => $event->id,
                            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
                            'justification' => $event->justification,
                            'type' => $index % 2 == 0 ? 'clock_in' : 'clock_out',
                        ];
                    });

                    $totalHoursWorked = $totalTimeWorked / 3600;
                    $normalHours = $totalHoursWorked >= 8 ? 8 : (int)$totalHoursWorked;
                    $extraHours = $totalHoursWorked - $normalHours;

                    return [
                        'day' => $eventsForDate->first()->timestamp->format('Y-m-d'),
                        'user_name' => $eventsForDate->first()->user->name,
                        'user_id' => $eventsForDate->first()->user->id,
                        'normal_hours_worked_on_day' => $this->convertDecimalToTime($normalHours),
                        'extra_hours_worked_on_day' => $this->convertDecimalToTime($extraHours),
                        'total_time_worked_in_seconds' => $totalTimeWorked,
                        'event_count' => $eventsForDate->count(),
                        'events' => $events,
                    ];
                });

            $totalTimeWorkedInSeconds = $clockEvents->sum('total_time_worked_in_seconds');
            $totalNormalHours = $clockEvents->map(function ($clockEvent) {
                return $this->convertTimeToDecimal($clockEvent['normal_hours_worked_on_day']);
            })->sum();

            return response()->json([
                'total_hours_worked' => $this->convertDecimalToTime($totalTimeWorkedInSeconds / 3600),
                'total_normal_hours_worked' => $this->convertDecimalToTime($totalNormalHours),
                'total_hour_balance' => $this->calculateBalanceOfHours(
                    8, //TODO: get from user
                    $totalTimeWorkedInSeconds / 3600,
                    $clockEvents->filter(function ($event) {
                        return $event['event_count'] >= 2;
                    })->count()
                ),
                'entries' => $clockEvents->values(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    //CLOCK CRUD

    public function getAllUserClockEntries(Request $request)
    {
        $clockEvents = ClockEvent::where('user_id', $request->user()->id)
            ->orderBy('timestamp', 'desc')
            ->get();
        return response()->json($clockEvents);
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
                return response()->json(['message' => 'Clock entry deleted successfully']);
            } else {
                return response()->json(['message' => 'Clock entry not found'], 404);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Failed to delete clock entry'], 500);
        }
    }

    public function insertClockEntry(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required',
                'timestamp' => 'required',
                'justification' => 'required',
            ]);

            $clockEvent = ClockEvent::create($validatedData);

            return response()->json(['message' => 'Clock entry inserted successfully at ' . $clockEvent['timestamp'] . ' with id ' . $clockEvent['id'], 'id' => $clockEvent['id'] ?? '']);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Failed to insert clock entry'], 500);
        }
    }

    public function updateClockEntry(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required',
                'timestamp' => 'required',
                'justification' => 'required',
            ]);

            $clockEvent = ClockEvent::find($validatedData['id']);

            if ($clockEvent) {
                $clockEvent->update($validatedData);

                return response()->json(['message' => 'Clock entry updated successfully to ' . $clockEvent['timestamp'] . ' with justification: ' . $clockEvent['justification']]);
            } else {
                return response()->json(['message' => 'Clock entry not found'], 404);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Failed to update clock entry'], 500);
        }
    }

    public function deleteAllClockEntries()
    {
        ClockEvent::truncate();

        return response()->json(['message' => 'All clock entries deleted successfully']);
    }

    //HOUR CALCULATION

    public function calculateHoursWorkedByPeriod(Request $request)
    {
        try {
            $query = $this->validateDataIntoQuery($request);
            $hourRate = $request['hour_rate'];

            $clockEvents = $query->orderBy('timestamp', 'asc')->get();

            $totalHoursWorkedPerDay = $this->calculateTotalHoursWorkedPerDay($clockEvents);

            return response()->json([
                'total_hours_worked' => $this->convertDecimalToTime(array_sum($totalHoursWorkedPerDay)),
                'total_money_earned' => number_format(array_sum($totalHoursWorkedPerDay) * $hourRate, 2),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    private function calculateTotalTimeWorked($eventsForDate)
    {
        $totalTimeWorked = 0;

        for ($i = 0; $i < count($eventsForDate); $i += 2) {
            $clockInEvent = $eventsForDate[$i];
            $clockOutEvent = $eventsForDate[$i + 1] ?? null;

            if ($clockOutEvent) {
                $timeWorked = $clockInEvent->timestamp->diffInSeconds($clockOutEvent->timestamp);
                $totalTimeWorked += $timeWorked;
            }
        }

        return $totalTimeWorked;
    }

    private function calculateTotalHoursWorkedPerDay($clockEvents)
    {
        $clockEventsGroupedByDay = $clockEvents->groupBy(function ($date) {
            return Carbon::parse($date->timestamp)->format('Y-m-d');
        });

        $totalHoursWorkedPerDay = [];

        foreach ($clockEventsGroupedByDay as $day => $eventsForDate) {
            $totalHoursWorkedPerDay[$day] = $this->calculateTotalTimeWorked($eventsForDate) / 3600;
        }

        return $totalHoursWorkedPerDay;
    }

    //UTILS

    private function validateDataIntoQuery(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $userId = $validatedData['user_id'];
        $startDate = $validatedData['start_date'] ?? Carbon::minValue();
        $endDate = $validatedData['end_date'] ?? Carbon::now();

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $query = ClockEvent::where('user_id', $userId)->with('user');

        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    private function calculateBalanceOfHours($workJourneyHours, $totalTimeWorked, $daysWorked)
    {
        $balanceOfHours = $totalTimeWorked - ($workJourneyHours * $daysWorked);
        return $this->convertDecimalToTime($balanceOfHours);
    }

    private function convertDecimalToTime($hoursDecimal)
    {
        $hours = intval($hoursDecimal);
        $decimalHours = $hoursDecimal - $hours;
        $minutes = round($decimalHours * 60);
        return sprintf("%d:%02d", $hours, $minutes);
    }

    private function convertTimeToDecimal($time)
    {
        $parts = explode(':', $time);
        $hours = $parts[0];
        $minutes = $parts[1];
        return $hours + ($minutes / 60);
    }
}

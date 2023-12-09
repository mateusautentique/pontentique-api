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

            $clockEvents = $query->orderBy('timestamp', 'desc')->get()
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
                            'type' => $index % 2 == 0 ? 'clock_out' : 'clock_in',
                        ];
                    });

                    return [
                        'day' => $eventsForDate->first()->timestamp->format('Y-m-d'),
                        'user_name' => $eventsForDate->first()->user->name,
                        'user_id' => $eventsForDate->first()->user->id,
                        'hours_worked_on_day' => (int)($totalTimeWorked / 3600),
                        'extra_minutes_worked_on_day' => (int)(($totalTimeWorked % 3600) / 60),
                        'events' => $events,
                    ];
                });

            return response()->json(['entry' => $clockEvents->values()]);
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

            return response()->json(['message' => 'Clock entry inserted successfully']);
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

                return response()->json(['message' => 'Clock entry updated successfully']);
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

            $clockEventsGroupedByDay = $clockEvents->groupBy(function ($date) {
                return Carbon::parse($date->timestamp)->format('Y-m-d');
            });

            $totalHoursWorkedPerDay = [];

            foreach ($clockEventsGroupedByDay as $day => $clockEvents) {
                $totalHoursWorked = 0;

                $totalHoursWorkedPerDay[$day] = $this->calculateTotalTimeWorked($clockEvents) / 3600;
            }

            return response()->json([
                'total_hours_worked' => (int)array_sum($totalHoursWorkedPerDay),
                'total_money_earned' => number_format((float)array_sum($totalHoursWorkedPerDay) * $hourRate, 2),
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

    //UTILS

    private function validateDataIntoQuery(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $userId = $validatedData['user_id'];
        $startDate = isset($validatedData['start_date']) ? Carbon::parse($validatedData['start_date']) : null;
        $endDate = isset($validatedData['end_date']) ? Carbon::parse($validatedData['end_date']) : null;

        $query = ClockEvent::where('user_id', $userId)->with('user');

        if ($startDate && !$endDate) {
            $endDate = Carbon::now();
        } elseif (!$startDate && $endDate) {
            $startDate = Carbon::minValue();
        } elseif (!$startDate && !$endDate) {
            $startDate = Carbon::minValue();
            $endDate = Carbon::now();
        }

        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }
}

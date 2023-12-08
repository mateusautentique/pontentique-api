<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ClockEvent;
use Carbon\Carbon;

class ClockController extends Controller
{
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

    public function showAllUserClockEntries(Request $request)
    {
        $clockEvents = ClockEvent::where('user_id', $request->user()->id)->get();
        return response()->json($clockEvents);
    }

    public function getClockEventsByPeriod(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
            ]);

            $userId = $validatedData['user_id'];
            $startDate = isset($validatedData['start_date']) ? Carbon::parse($validatedData['start_date']) : null;
            $endDate = isset($validatedData['end_date']) ? Carbon::parse($validatedData['end_date']) : null;

            $query = ClockEvent::where('user_id', $userId)->with('user');

            if ($startDate && $endDate) {
                $query->whereBetween('timestamp', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('timestamp', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('timestamp', '<=', $endDate);
            }

            $clockEvents = $query->get()
                ->groupBy(function ($event) {
                    return $event->timestamp->format('Y-m-d');
                })
                ->map(function ($eventsForDate, $date) {
                    return $eventsForDate->map(function ($event, $index) {
                        return [
                            'user_name' => $event->user->name,
                            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
                            'justification' => $event->justification,
                            'type' => $index % 2 == 0 ? 'clock_in' : 'clock_out',
                        ];
                    });
                });

            return response()->json($clockEvents);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'An error occurred'], 500);
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
}

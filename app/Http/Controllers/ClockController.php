<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClockEvent;


class ClockController extends Controller
{
    public function registerClock(Request $request)
    {
        $lastClockEvent = ClockEvent::where('user_id', $request->user()->id)->latest('timestamp')->first();

        $isEntry = !$lastClockEvent || $lastClockEvent->event_type == 'leave';

        $clockEvent = ClockEvent::create([
            'user_id' => $request->user()->id,
            'event_type' => $isEntry ? 'entry' : 'leave',
            'timestamp' => now(),
        ]);

        $message = $isEntry ? 'Clocked in successfully' : 'Clocked out successfully';

        return response()->json(['message' => $message]);
    }

    public function showAllUserClockEntries(Request $request)
    {
        $clockEvents = ClockEvent::where('user_id', $request->user()->id)->get();
        return response()->json($clockEvents);
    }

    //filter user clock entries by two dates

    public function showUserClockEntriesByDate(Request $request)
    {
        $clockEvents = ClockEvent::where('user_id', $request->user()->id)->whereBetween('timestamp', [$request->start_date, $request->end_date])->get();
        return response()->json($clockEvents);
    }

    public function deleteAllClockEntries()
    {
        ClockEvent::truncate();

        return response()->json(['message' => 'All clock entries deleted successfully']);
    }
}

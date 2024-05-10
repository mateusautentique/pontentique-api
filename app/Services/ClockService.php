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
use Illuminate\Support\Facades\DB;

class ClockService
{
    public function getAllUserClockEntries(int $id): array
    {
        return ClockEvent::where('user_id', $id)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }

    public function getDeletedEntries()
    {
        $deletedClockEvents = ClockEvent::onlyTrashed()->get()->makeVisible('deleted_at');
        return $deletedClockEvents;
    }

    public function deleteClockEntry(int $clock_id, string $justification): void
    {
        $clockEvent = ClockEvent::find($clock_id);
        if ($clockEvent) {
            $clockEvent->justification = $justification;
            $clockEvent->save();
            $clockEvent->delete();
        }
    }

    public function insertClockEntry(array $data): void
    {
        ClockEvent::create($data);
    }

    public function updateClockEntry(array $data): void
    {
        $clockEvent = ClockEvent::find($data['id']);
        if ($clockEvent) {
            $clockEvent->update($data);
        }
    }

    public function deleteAllClockEntries(): string
    {
        if (env('APP_DEBUG') == false)
            return 'This action is only allowed in debug mode';

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('clock_events')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return 'Todas as entradas foram deletadas com sucesso';
    }
}

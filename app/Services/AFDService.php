<?php
namespace App\Services;

use App\Models\ClockEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AFDService
{
    function generateAFDTLine(ClockEvent $event, string $action): string
    {
        $counter = Cache::rememberForever('afdt_counter', function () {
            return 0;
        });

        $nsr = str_pad($counter, 9, '0', STR_PAD_LEFT);
        $type = '2';
        $timestamp = $event->timestamp->format('YYYYmmddHHii');
        $pis = str_pad($event->user->pis, 11, '0', STR_PAD_LEFT);
        $rep = '00000000000000000';
        //verify ammount of normal clock punches on day
        // $dayOff = $event->day_off ? '1' : '0';
        // $doctor = $event->doctor ? '1' : '0';
        // $controlId = $event->control_id ? '1' : '0';
        // $justification = substr($event->justification, 0, 255);

        // $registry = $nsr . $model . $type . $userCpf . $id . $timestamp . 
        //             $dayOff . $doctor . $controlId . $justification;

        $registry = "";

        Cache::forever('afdt_counter', $counter + 1);

        return $registry;
    }

    function sendAFDT(string $registry): void
    {
        $filePath = 'PontentiqueAFDT.txt';
        Storage::disk('local')->append($filePath, $registry);
    }

    public function getAFDT()
    {
        $filePath = 'PontentiqueAFDT.txt';
        return Storage::disk('local')->get($filePath);
    }
}
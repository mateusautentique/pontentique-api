<?php
namespace App\Services;

use App\Models\ClockEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LogRegistryService
{
    function generateClockLogLine(ClockEvent $event, string $action): string
    {
        $counter = Cache::rememberForever('log_counter', function () {
            return 0;
        });

        $nsr = str_pad($counter, 9, '0', STR_PAD_LEFT);
        $model = 'C';
        $type = $action === 'create' ? 'C' : ($action === 'update' ? 'U' : 'D');
        $userCpf = str_pad($event->user->cpf, 11, '0', STR_PAD_LEFT);
        $id = str_pad($event->id, 9, '0', STR_PAD_LEFT);
        $timestamp = $event->timestamp->format('YmdHis');
        $dayOff = $event->day_off ? '1' : '0';
        $doctor = $event->doctor ? '1' : '0';
        $controlId = $event->control_id ? '1' : '0';
        $justification = substr($event->justification, 0, 255);

        $registry = $nsr . $model . $type . $userCpf . $id . $timestamp . 
                    $dayOff . $doctor . $controlId . $justification;

        Cache::forever('log_counter', $counter + 1);

        return $registry;
    }

    function generateUserLogLine(User $user, string $action): string
    {
        $counter = Cache::rememberForever('log_counter', function () {
            return 0;
        });

        $nsr = str_pad($counter, 9, '0', STR_PAD_LEFT);
        $model = 'U';
        $type = $action === 'create' ? 'C' : ($action === 'update' ? 'U' : 'D');
        $userCpf = str_pad($user->cpf, 11, '0', STR_PAD_LEFT);
        $id = str_pad($user->id, 4, '0', STR_PAD_LEFT);
        $role = $user->role === 'admin' ? 1 : 0;
        $timestamp = $user->updated_at->format('YmdHis');
        $name = substr($user->name, 0, 255);

        $registry = $nsr . $model . $type . $userCpf . $id . $role . $timestamp . $name;

        Cache::forever('log_counter', $counter + 1);

        return $registry;
    }

    function sendLogs(string $registry): void
    {
        $filePath = 'pontentique_logs.txt';
        Storage::disk('local')->append($filePath, $registry);
    }

    public function getLogs()
    {
        $filePath = 'pontentique_logs.txt';
        return Storage::disk('local')->get($filePath);
    }
}
    
    /*
    /   Registro de ponto
    /   NSR: 000000000
    /   MODELO: U, C -> (User, Clock)
    /   TIPO: C, U, D -> (Create, Update, Delete)
    /   USER CPF: 00000000000
    /   ID DO REGISTRO DE PONTO: 000000000
    /   TIMESTAMP: YYYYMMDDHHMMSS -> Timestamp editada, caso for update, representa
    /   a data alterada
    /   DAYOFF: 0, 1
    /   DOCTOR: 0, 1
    /   CONTROLID: 0, 1
    /   JUSTIFICATION: 0-255
    */

    /*
    /   Registro de usuário
    /   NSR: 000000000
    /   MODELO: U, C -> (User, Clock)
    /   TIPO: C, U, D-> (Create, Update, Delete)
    /   USER CPF: 00000000000
    /   ID DO USUÁRIO: 0000
    /   ROLE: 1 -> Admin, 0 -> User
    /   NOME: 0-255
    /   TIMESTAMP: YYYYMMDDHHMMSS -> Updated_at do usuário
    */
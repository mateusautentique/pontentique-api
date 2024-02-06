<?php
namespace App\Services;

use App\Models\ClockEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AFDRegistryService
{
    function generateClockRegistryLine(ClockEvent $event, string $action): string
    {
        $counter = Cache::rememberForever('afd_counter', function () {
            return 0;
        });

        $nsr = str_pad($counter, 9, '0', STR_PAD_LEFT);
        $type = $action === 'create' ? 'C' : ($action === 'update' ? 'U' : 'D');
        $userCpf = str_pad($event->user->cpf, 11, '0', STR_PAD_LEFT);
        $id = str_pad($event->id, 9, '0', STR_PAD_LEFT);
        $timestamp = $event->timestamp->format('YmdHis');
        $dayOff = $event->day_off ? '1' : '0';
        $doctor = $event->doctor ? '1' : '0';
        $controlId = $event->control_id ? '1' : '0';
        $justification = substr($event->justification, 0, 255);

        $registry = $nsr . $type . $userCpf . $id . $timestamp . 
                    $dayOff . $doctor . $controlId . $justification;

        Cache::forever('afd_counter', $counter + 1);

        return $registry;
    }

    function generateUserRegistryLine(User $user, string $action): string
    {
        $counter = Cache::rememberForever('afd_counter', function () {
            return 0;
        });

        $nsr = str_pad($counter, 9, '0', STR_PAD_LEFT);
        $type = $action === 'create' ? 'C' : ($action === 'update' ? 'U' : 'D');
        $userCpf = str_pad($user->cpf, 11, '0', STR_PAD_LEFT);
        $id = str_pad($user->id, 4, '0', STR_PAD_LEFT);
        $role = $user->role === 'admin' ? 1 : 0;
        $timestamp = $user->updated_at->format('YmdHis');
        $name = substr($user->name, 0, 255);

        $registry = $nsr . $type . $userCpf . $id . $role . $timestamp . $name;

        Cache::forever('afd_counter', $counter + 1);

        return $registry;
    }

    function sendRegistryLine(string $registry): void
    {
        $filePath = base_path('infra/AFDRegistryFile.txt');
        file_put_contents($filePath, $registry . PHP_EOL, FILE_APPEND);
    }

    public function getAFD(): string
    {
        $filePath = base_path('infra/AFDRegistryFile.txt');
        return file_get_contents($filePath);
    }
}
    
    /*
    /   Registro de ponto
    /   NSR: 000000000
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
    /   TIPO: C, U, D-> (Create, Update, Delete)
    /   USER CPF: 00000000000
    /   ID DO USUÁRIO: 0000
    /   ROLE: 1 -> Admin, 0 -> User
    /   NOME: 0-255
    /   TIMESTAMP: YYYYMMDDHHMMSS -> Updated_at do usuário
    */
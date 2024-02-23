<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ClockEvent;

class GetRHIDEntries extends Command
{
    //docker exec -it pontentiqueapi-php-1 bash -c "php artisan app:get-rh-id-entries"
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-rh-id-entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get entries from RH ID and insert them into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileContents = file_get_contents('rhid_afd/rhidafd.txt');
        $lines = explode("\n", $fileContents);

        $users = User::all()->keyBy('cpf');
        
        foreach ($lines as $line) {
            if (strlen($line) < 10 || $line[9] != '7') {
                continue;
            }
            
            $date = substr($line, 10, 19);
            $timestamp = str_replace('T', ' ', $date);
            $cpf = substr($line, 35, 11);

            $user = $users->get($cpf);

            if ($user) {
                ClockEvent::firstOrCreate([
                    'user_id' => $user->id,
                    'timestamp' => $timestamp,
                    'day_off' => false,
                    'doctor' => false,
                    'control_id' => false,
                    'rh_id' => true,
                ]);
            }
        }
    }
}

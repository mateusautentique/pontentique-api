<?php

namespace App\Console\Commands;

use App\Services\ControlID;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DateTime;
use App\Models\User;
use App\Models\ClockEvent;

class GetControlIdEntries extends Command
{
    //docker exec -it pontentiqueapi-php-1 bash -c "php artisan app:get-control-id-entries"
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-control-id-entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get entries from Control ID and insert them into the database';

    /**
     * Execute the console command.
     */
    public function handle(ControlID $controlID): void
    {
        try {
            $afd = $controlID->getAFD();
            $this->decodeAFD($afd);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function decodeAFD($afd): void
    {
        $lines = explode("\n", $afd);

        $users = User::all()->keyBy('cpf');

        foreach($lines as $line){
            if ($line[9] == '3'){
                $date = substr($line, 10, 14);
                $timestamp = $this->dateFormat($date);
                $cpf = substr($line, 23, 11);

                $user = $users->get($cpf);

                if ($user){
                    ClockEvent::firstOrCreate([
                        'user_id' => $user->id,
                        'timestamp' => $timestamp,
                        'controlId' => true,
                    ]);
                }
            }
        }
    }

    private function dateFormat($dateString): string
    {
        $date = DateTime::createFromFormat('dmYHis', $dateString);
        return $date->format('Y-m-d H:i:s');
    }
}

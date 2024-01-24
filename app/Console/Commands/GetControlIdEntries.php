<?php

namespace App\Console\Commands;

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
    public function handle()
    {
        try {
            $session = $this->loginControlId()['session'];
    
            $afd = $this->getAFD($session);
    
            $this->decodeAFD($afd);
    
            $this->logoutControlId($session);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function loginControlId(){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post(env('CONTROL_ID_MACHINE_HOST') . '/login.fcgi', [
            'login' => 'admin',
            'password' => 'admin'
        ]);

        return $response->json();
    }

    private function logoutControlId($session){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post(env('CONTROL_ID_MACHINE_HOST') . '/logout.fcgi', [
            'session' => $session
        ]);

        return $response->json();
    }

    private function getAFD($session){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post(env('CONTROL_ID_MACHINE_HOST') . '/get_afd.fcgi', [
            'session' => $session,
            'mode' => '671'
        ]);

        return $response->body();
    }

    private function decodeAFD($afd){
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

    private function dateFormat($dateString) {
        $date = DateTime::createFromFormat('dmYHis', $dateString);
        return $date->format('Y-m-d H:i:s');
    }
}

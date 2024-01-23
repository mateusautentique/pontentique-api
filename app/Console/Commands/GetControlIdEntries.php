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
    protected $controlIdHost = 'https://192.168.1.249:8743/';
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
            $loginInfo = $this->loginControlId();
    
            $session = $loginInfo['session'];
    
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
        ])->withoutVerifying()->post($this->controlIdHost . 'login.fcgi', [
            'login' => 'admin',
            'password' => 'admin'
        ]);

        return $response->json();
    }

    private function logoutControlId($session){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post($this->controlIdHost . 'login.fcgi', [
            'session' => $session
        ]);

        return $response->json();
    }

    private function getAFD($session){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post($this->controlIdHost . 'get_afd.fcgi', [
            'session' => $session,
            'mode' => '671'
        ]);

        return $response->body();
    }

    private function decodeAFD($afd){
        $lines = explode("\n", $afd);
    
        foreach($lines as $line){
            if ($line[9] == '3'){
                $date = substr($line, 10, 14);
                $timestamp = $this->dateFormat($date);
                $cpf = substr($line, 23, 11);
                
                $user = User::where('cpf', $cpf)->first();
                $userId = $user ? $user->id : null;
    
                if ($userId){
                    ClockEvent::firstOrCreate([
                        'user_id' => $userId, 
                        'timestamp' => $timestamp,
                        'controlId' => true,
                    ]);
                }
            }
        }
    }

    private function dateFormat($dateString) {
        $date = DateTime::createFromFormat('dmYHis', $dateString);
        return $timestamp = $date->format('Y-m-d H:i:s');
    }
}

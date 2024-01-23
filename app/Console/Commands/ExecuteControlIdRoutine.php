<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ExecuteControlIdRoutine extends Command
{
    //docker exec -it pontentiqueapi-php-1 bash -c "php artisan app:execute-control-id-routine"
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:execute-control-id-routine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wakes up the Control ID machine and runs the routine to get the entries and insert them into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $session = $this->loginControlId()['session'];
            
            $this->applyPortForward($session);
            
            Artisan::call('app:get-control-id-entries');

            $this->logoutControlId($session);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function loginControlId(){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post(env('CONTROL_ID_HOST') . '/login.fcgi', [
            'login' => env('CONTROL_ID_USER'),
            'password' => env('CONTROL_ID_PASSWORD')
        ]);
    
        return $response->json();
    }

    private function applyPortForward($session){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Cookie' => 'session=' . $session
        ])->withoutVerifying()->post(env('CONTROL_ID_HOST') . '/firewall_edit_portforward_rule.fcgi', [
            'index' => 1,
            'external_port' => 8743,
            'local_address' => '192.168.0.132',
            'local_port' => 8743,
            'protocol' => 0
        ]);
    
        return $response->json();
    }

    private function logoutControlId($session){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post(env('CONTROL_ID_HOST') . '/logout.fcgi?session=' . $session, []);
    
        return $response->json();
    }
}

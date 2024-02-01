<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;

class TestAPIThrottle extends Command
{
    //docker exec -it pontentiqueapi-php-1 bash -c "php artisan app:test-api-throttle"
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-api-throttle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests the Throttle value set for the API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = $this->login()['token'];

        $this->testThrottle(501, $token);

        $this->logout($token);
    }

    private function login()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->withoutVerifying()->post('http://192.168.1.250:8000/api/login', [
            'cpf' => '12345678900',
            'password' => '12345'
        ]);

        return $response->json();
    }

    private function testThrottle($requests, $token)
    {
        for ($i = 0; $i < $requests; $i++) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->withoutVerifying()->get('http://192.168.1.250:8000/api/admin/manageUsers');

            $this->info("Response status: " . $response->status());

            if ($response->failed()) {
                $this->error("Error: " . $response->body());
            }
        }
    }

    private function logout($token)
    {
        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->withoutVerifying()->post('http://192.168.1.250:8000/api/logout');
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ControlID
{
    protected string $base_url;
    protected string $session;

    protected Http $http;


    public function __construct(Http $http)
    {
        $this->http = $http;
        $this->base_url = env('CONTROL_ID_MACHINE_HOST');
        $this->login();
    }

    public function __destruct()
    {
        $this->logout();
    }

    private function makeRequest(string $endpoint, array $body): Response
    {
        return $this->http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post($this->base_url . $endpoint, $body);
    }

    private function login(): void
    {
        $response = $this->makeRequest('/login.fcgi', [
            'login' => 'admin',
            'password' => 'admin'
        ]);

        $this->session = $response['session'];
    }

    private function logout(): void
    {
        $this->makeRequest('/logout.fcgi', [
            'session' => $this->session
        ]);
    }

    public function getAFD(): string
    {
        $response = $this->makeRequest('/get_afd.fcgi', [
            'session' => $this->session,
            'mode' => '671'
        ]);

        return $response->body();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $token = $this->authService->register($request->validated());
            return response(['token' => $token], 200);
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(), 
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $token = $this->authService->login($request->validated());
            return response(['token' => $token], 200);
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(), 
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $message = $this->authService->logout($user);
            return response(['message' => $message], 200);
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(), 
            ], 500);
        }
    }

    public function validateToken()
    {
        return $this->authService->validateToken();
    }

    public function getLoggedUserInfo()
    {
        return $this->authService->getLoggedUserInfo(auth()->user());
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

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
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $token = $this->authService->login($request->validated());
            return response(['token' => $token], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response([ 'message' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ( ! $user) {
            return response(['message' => 'User not found'], 404);
        }

        try {
            $this->authService->logout($user);
            return response(['message' => 'Successfully logged out'], 200);
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function validateToken()
    {
        return response([
            'message' => $this->authService->validateToken()
        ], 200);
    }

    public function getLoggedUserInfo()
    {
        try {
            $user = auth()->user();
            if ( ! $user) {
                return response(['message' => 'User not found'], 404);
            }

            return $user;
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

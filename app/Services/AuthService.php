<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class AuthService
{
    public function register(array $data): string
    {
        try {
            $data['password'] = Hash::make($data['password']);
            $user = User::create($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User registration failed',
                'error' => $e->getMessage()
            ], 500);
        }

        return $user->createToken('userToken')->accessToken;
    }

    public function login(array $data): string
    {
        $user = User::where('cpf', $data['cpf'])->first();
        if ($user && Hash::check($data['password'], $user->password)) {
            return $user->createToken('userToken')->accessToken;
        }
    }

    public function logout(User $user): string
    {
        if (!$user) {
            return 'User not found';
        }
        foreach ($user->tokens as $token) {
            $token->revoke();
        }
        return 'Successfully logged out';
    }

    public function validateToken(): Response
    {
        return Auth::guard('api')->check()
            ? response(['message' => true], 200)
            : response(['message' => false], 401);
    }

    public function getLoggedUserInfo(User $user): JsonResponse
    {
        return response()->json($user);
    }
}

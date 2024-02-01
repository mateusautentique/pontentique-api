<?php
namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthService
{
    public function register(array $data)
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
    
        $token = $user->createToken('userToken')->accessToken;
        return response(['token' => $token], 200);
    }

    public function login(array $data)
    {
        try {
            $user = User::where('cpf', $data['cpf'])->first();
            if ($user && Hash::check($data['password'], $user->password)) {
                $token = $user->createToken('userToken')->accessToken;
                return response(['token' => $token], 200);
            } else {
                return response(['error' => 'Usuário ou senha incorretos'], 401);
            }
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(), 
            ], 500);
        }
    }

    public function logout($user)
    {
        try {
            if (!$user) {
                return response()->json([
                    'message' => 'Not logged in'
                ], 401);
            }
            foreach ($user->tokens as $token) {
                $token->revoke();
            }
            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function validateToken()
    {
        return Auth::guard('api')->check()
            ? response(['message' => true], 200)
            : response(['message' => false], 401);
    }

    public function getLoggedUserInfo()
    {
        try {
            $user = Auth::user();
            if ($user) {
                return response()->json($user);
            } else {
                return response()->json(['error' => 'Not authenticated'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\HasApiTokens;
use App\Models\User;

class AuthController extends Controller
{
    use HasApiTokens;

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'cpf' => 'required|string|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:5|confirmed',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'message' => 'Validation failed',
                'error' => $e->errors()
            ], 422);
        }

        try {
            $request['password'] = Hash::make($request['password']);
            $user = User::create($request->all());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User registration failed',
                'error' => $e->getMessage()
            ], 500);
        }

        $token = $user->createToken('userToken')->accessToken;
        return response(['token' => $token], 200);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'cpf' => 'required|string',
                'password' => 'required|string|min:5',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'message' => 'CPF ou senha inválidos',
                'error' => $e->errors()
            ], 422);
        }

        try {
            $user = User::where('cpf', $request->cpf)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                $token = $user->createToken('userToken')->accessToken;
                return response(['token' => $token], 200);
            } else {
                return response(['error' => 'Usuário ou senha incorretos'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function validateToken()
    {
        return Auth::guard('api')->check()
            ? response(['message' => true], 200)
            : response(['message' => false], 422);
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
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

    public function getUserInfo()
    {
        $user = Auth::user();

        if ($user) {
            return response()->json([
                'id' => $user->id,
                'user_name' => $user->name,
            ]);
        } else {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
    }
}

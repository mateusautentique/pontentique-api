<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthService
{
    public function register(array $data): string
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = User::where('role', 'admin')->exists() ? 'user' : 'admin';

        $user = User::create($data);
        return $user->createToken('userToken')->accessToken;
    }

    public function login(array $data)
    {
        $user = User::where('cpf', $data['cpf'])->first();
        if (!$user) {
            throw new ModelNotFoundException('Usuário não encontrado');
        }
        if (!Hash::check($data['password'], $user->password)) {
            throw new \Exception('Credenciais inválidas');
        }
        return $user->createToken('userToken')->accessToken;
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

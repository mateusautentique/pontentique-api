<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
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
            return response(['errors' => $e->errors()], 422);
        }

        try {
            $request['password'] = Hash::make($request['password']);
            $user = User::create($request->all());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
            return response(['errors' => $e->errors()], 422);
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
}

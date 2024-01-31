<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\HasApiTokens;
use App\Models\User;

class AuthController extends Controller
{
    use HasApiTokens;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:5|confirmed',
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->has('password') && str_contains($errors->first('password'), 'confirmação')) {
                return response(['error' => $errors->first('password')], 494);
            }        

            $fields = ['name', 'cpf', 'email', 'password'];
            $statusCodes = [490, 491, 492, 493];
        
            foreach ($fields as $index => $field) {
                if ($errors->has($field)) {
                    return response(['error' => $errors->first($field)], $statusCodes[$index]);
                }
            }
            return response(['error' => $errors], 422);
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
                'cpf' => 'required|size:11|string',
                'password' => 'required|string|min:5',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'message' => 'CPF ou senha inválidos',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $user = User::where('cpf', $request->cpf)->first();
            if ($user && Hash::check($request->password, $user->password)) {
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

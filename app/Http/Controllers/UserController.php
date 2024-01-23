<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ClockEvent;
use Carbon\Carbon;

class UserController extends Controller
{
    public function getAllUsers()
    {
        try {
            $users = User::all();
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUserById(Request $request)
    {  
        try {
            $id = $request->user_id;

            $user = User::findOrFail($id);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function insertUser(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'cpf' => 'required|unique:users',
                'role' => 'sometimes|in:admin,user',
                'password' => 'required|confirmed'
            ]);
            $request['password'] = Hash::make($request['password']);  
            $validarequesttedInfo['role'] = $request['role'] === 'admin' ? 'admin' : 'user';
    
            $user = User::create($request);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required',
            'email' => ['required', 'email', Rule::unique('users')->ignore($request->user_id)],
            'cpf' => ['required', Rule::unique('users')->ignore($request->user_id)],
            'role' => 'sometimes|in:admin,user',
            'password' => 'sometimes|confirmed'
        ]);
    
        try {
            $user = User::findOrFail($request->user_id);
    
            $data = $request->all();
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }
    
            $user->update($data);
    
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        try {
            $id = $request->user_id;
            
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'Usuário deletado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkCurrentUserStatus(Request $request){
        try {
            $request->validate([
                'user_id' => 'required',
            ]);

            $todayEntriesCount = ClockEvent::where('user_id', $request['user_id'])->whereDate('timestamp', Carbon::today())->count();

            if ($todayEntriesCount == 0) {
                return response()->json(['message' => 'Gray']);
            } else if ($todayEntriesCount % 2 == 1) {
                return response()->json(['message' => 'Green']);
            } else {
                return response()->json(['message' => 'Red']);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }
}

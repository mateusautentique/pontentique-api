<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ClockEvent;
use Carbon\Carbon;

class UserService
{
    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserById(int $id)
    {
        return User::findOrFail($id);
    }

    public function insertUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = $data['role'] === 'admin' ? 'admin' : 'user';
        return User::create($data);
    }

    public function updateUser(array $data): User
    {
        $user = User::findOrFail($data['user_id']);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return $user;
    }

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);
        $user->delete();
    }

    public function checkUserStatus(int $id): string
    {
        $todayEntriesCount = ClockEvent::where('user_id', $id)
            ->whereDate('timestamp', Carbon::today())->count();

        if ($todayEntriesCount == 0) {
            return 'Gray';
        } else if ($todayEntriesCount % 2 == 1) {
            return 'Green';
        } else {
            return 'Red';
        }
    }
}

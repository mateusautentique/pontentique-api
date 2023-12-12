<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClockController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validateToken', [AuthController::class, 'validateToken']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('user')->group(function () {
        Route::post('/punchClock', [ClockController::class, 'registerClock']);
        Route::get('/userEntries', [ClockController::class, 'getAllUserClockEntries']);
        Route::post('/userEntries', [ClockController::class, 'getClockEventsByPeriod']);
        Route::post('/calculateHours', [ClockController::class, 'calculateHoursWorkedByPeriod']);
    });

    Route::prefix('admin')->group(function () {
        Route::prefix('userEntries')->group(function () {
            Route::get('/', [ClockController::class, 'getAllUserClockEntries']);
            Route::post('/', [ClockController::class, 'insertClockEntry']);
            Route::put('/', [ClockController::class, 'updateClockEntry']);
            Route::delete('/', [ClockController::class, 'deleteClockEntry']);
        });

        Route::prefix('manageUsers')->group(function () {
            Route::get('/', [UserController::class, 'getAllUsers']);
            Route::post('/user', [UserController::class, 'insertUser']);
            Route::get('/user/{id}', [UserController::class, 'getUserById']);
            Route::put('/user/{id}', [UserController::class, 'updateUser']);
            Route::delete('/user/{id}', [UserController::class, 'deleteUser']);
        });
    });
});

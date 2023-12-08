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

Route::middleware('auth:api')->group(function () {
    //User routes
    Route::prefix('user')->group(function () {
        Route::post('/punchClock', [ClockController::class, 'registerClock']);
        Route::get('/userEntries', [ClockController::class, 'getAllUserClockEntries']);
        Route::post('/userEntries', [ClockController::class, 'getClockEventsByPeriod']);
        Route::post('/calculateHours', [ClockController::class, 'calculateHoursWorkedByPeriod']);
    });

    //Admin routes
    Route::prefix('admin')->group(function () {
        Route::prefix('userEntries')->group(function () {
            Route::post('/', [ClockController::class, 'insertClockEntry']);
            Route::put('/{id}', [ClockController::class, 'updateClockEntry']);
            Route::delete('/{id}', [ClockController::class, 'deleteClockEntry']);
            Route::delete('/clear', [ClockController::class, 'deleteAllClockEntries']);
        });

        Route::apiResource('users', UserController::class);
    });
});

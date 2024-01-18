<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\TicketController;

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
Route::get('/validateToken', [AuthController::class, 'validateToken']);

//Route::get('/deleteEntries', [ClockController::class, 'deleteAllClockEntries']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('user')->group(function () {
        Route::get('/', [AuthController::class, 'getLoggedUserInfo']);
        Route::post('/punchClock', [ClockController::class, 'registerClock']);
        Route::post('/userEntries', [ClockController::class, 'getClockEventsByPeriod']);
        Route::post('/ticket', [TicketController::class, 'createTicket']);
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

        Route::prefix('manageTickets')->group(function () {
            Route::get('/', [TicketController::class, 'showAllTickets']);
            Route::get('/active', [TicketController::class, 'showAllActiveTickets']);
            Route::post('/user', [TicketController::class, 'showAllUserTickets']);
            Route::put('/handle', [TicketController::class, 'handleTicket']);
        });
    });

    Route::delete('/test', [TicketController::class, 'dropAllTickets']);
});

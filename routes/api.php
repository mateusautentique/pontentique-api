<?php

use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AFDController;

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

Route::middleware('api.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('user')->group(function () {
        Route::get('/', [AuthController::class, 'getLoggedUserInfo']);
        Route::post('/punchClock', [ClockController::class, 'registerClock']);
        Route::post('/userEntries', [ClockController::class, 'getClockReport']);
        Route::post('/ticket', [TicketController::class, 'createTicket']);

        Route::prefix('setDayOff')->group(function () {
            Route::post('/', [ClockController::class, 'setDayOffForDate']);
        });

    });

    Route::middleware('role:admin')->group(function (){
        Route::get('/getSystemLogs', [LogController::class, 'getSystemLogs']);
        Route::get('/getAFDT', [AFDController::class, 'getAFDT']);
        Route::get('/getACJEF', [AFDController::class, 'getACJEF']);

        Route::prefix('admin')->group(function () {
            Route::prefix('userEntries')->group(function () {
                Route::get('/deleted', [ClockController::class, 'getDeletedClockEntries']);
                Route::get('/{id}', [ClockController::class, 'getAllUserClockEntries']);
                Route::post('/', [ClockController::class, 'insertClockEntry']);
                Route::put('/', [ClockController::class, 'updateClockEntry']);
                Route::delete('/', [ClockController::class, 'deleteClockEntry']);
            });

            Route::prefix('manageUsers')->group(function () {
                Route::get('/', [UserController::class, 'getAllUsers']);
                Route::get('/user/{id}', [UserController::class, 'getUserById']);
                Route::post('/user', [UserController::class, 'insertUser']);
                Route::put('/user/', [UserController::class, 'updateUser']);
                Route::delete('/user/{id}', [UserController::class, 'deleteUser']);
                Route::get('/user/status/{id}', [UserController::class, 'checkUserStatus']);
            });

            Route::prefix('manageTickets')->group(function () {
                Route::get('/', [TicketController::class, 'showAllTickets']);
                Route::get('/active', [TicketController::class, 'showAllActiveTickets']);
                Route::get('/user/{id}', [TicketController::class, 'showAllUserTickets']);
                Route::put('/handle', [TicketController::class, 'handleTicket']);
            });
        });
    });

    // Route::prefix('test')->group(function () {
    //     Route::delete('/tickets', [TicketController::class, 'dropAllTickets']);
    //     Route::delete('/deleteEntries', [ClockController::class, 'deleteAllClockEntries']);
    // });
});

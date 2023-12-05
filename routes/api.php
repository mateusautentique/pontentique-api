<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

Route::get('/db-test', function () {
    try {
        Log::info('Attempting to connect to database...');
        DB::connection()->getPdo();
        Log::info('Successfully connected to the database.');
        return response()->json(['message' => 'Successfully connected to the database.'], 200);
    } catch (\Exception $e) {
        Log::error('Could not connect to the database.', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Could not connect to the database. Please check your configuration.'], 500);
    }
});

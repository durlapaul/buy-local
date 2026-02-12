<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/spaces', [SpaceController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::delete('/account', [UserController::class, 'deleteAccount']);
    });

    Route::get('/spaces/managed', [SpaceController::class, 'managed']);
    Route::post('/spaces', [SpaceController::class, 'store']);
    Route::get('/spaces/{space}', [SpaceController::class, 'show']);
    Route::put('/spaces/{space}', [SpaceController::class, 'update']);
    Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
    
    Route::post('/spaces/{space}/assign-user', [SpaceController::class, 'assignUser']);
    Route::delete('/spaces/{space}/users/{user}', [SpaceController::class, 'removeUser']);
    Route::get('/spaces/{space}/users', [SpaceController::class, 'users']);
});
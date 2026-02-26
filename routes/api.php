<?php

use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{entity}', [ProductController::class, 'show']);
Route::get('/categories', [ProductCategoryController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{entity}', [ProductController::class, 'update']);
    Route::patch('/products/{entity}', [ProductController::class, 'update']);
    Route::delete('/products/{entity}', [ProductController::class, 'destroy']);

    Route::post('/products/{entity}/images', [ProductController::class, 'addImage']);
    Route::delete('/products/{entity}/images/{mediaId}', [ProductController::class, 'deleteImage']);
    Route::post('/products/{entity}/images/reorder', [ProductController::class, 'reorderImages']);

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::delete('/account', [UserController::class, 'deleteAccount']);
    });
});
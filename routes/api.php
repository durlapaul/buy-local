<?php

use App\Http\Controllers\Api\FavouriteController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Middleware\OptionalSanctumAuth;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/email/resend', [AuthController::class, 'resend'])
    ->middleware('throttle:6,1');

Route::get('/categories', [ProductCategoryController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/products/deleted', [ProductController::class, 'deleted']);
    Route::get('/products/for-user', [ProductController::class, 'getProductsForUser']);
    
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{entity}', [ProductController::class, 'update']);
    Route::patch('/products/{entity}', [ProductController::class, 'update']);
    Route::delete('/products/{entity}', [ProductController::class, 'destroy']);
    Route::post('/products/update-status/{entity}', [ProductController::class, 'updateStatus']);
    Route::post('/products/get-pending', [ProductController::class, 'getPendingProducts']);
    Route::post('/products/restore/{id}', [ProductController::class, 'restore']);

    Route::get('/favourites', [FavouriteController::class, 'index']);
    Route::post('/products/{product}/favourite', [FavouriteController::class, 'toggle']);

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

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'indexForBuyer']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{entity}', [OrderController::class, 'show']);
        Route::post('/{entity}/cancel', [OrderController::class, 'cancel']);
        Route::post('/{order}/reviews', [ProductReviewController::class, 'store']);
    });

    Route::prefix('seller/orders')->group(function () {
        Route::get('/', [OrderController::class, 'indexForSeller']);
        Route::get('/{entity}', [OrderController::class, 'show']);
        Route::post('/{entity}/confirm', [OrderController::class, 'confirm']);
        Route::post('/{entity}/ship', [OrderController::class, 'ship']);
        Route::post('/{entity}/deliver', [OrderController::class, 'deliver']);
        Route::post('/{entity}/reject', [OrderController::class, 'reject']);
    });

    Route::prefix('admin/orders')->group(function () {
        Route::get('/', [OrderController::class, 'indexForAdmin']);
        Route::get('/{entity}', [OrderController::class, 'show']);
    });

});

Route::middleware(OptionalSanctumAuth::class)->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{entity}', [ProductController::class, 'show']);
    Route::post('/products/by-ids', [ProductController::class, 'getByIds']);
});
<?php

use App\Http\Controllers\API\UserAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/register', [UserAuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-session', [UserAuthController::class, 'checkSession']);
    Route::post('/logout', [UserAuthController::class, 'logout']);
    
    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserAuthController::class, 'fetchUsers']);
        Route::post('/users/role', [UserAuthController::class, 'updateUserRole']);
        Route::post('/delete-users', [UserAuthController::class, 'deleteUser']);
    });
});
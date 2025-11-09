<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle user authentication for the Task Management API.
| All routes are stateless and use Laravel Sanctum for token-based auth.
|
*/

Route::prefix('auth')->group(function () {
    // POST /api/auth/login
    Route::post('/login', [AuthController::class, 'login']);

    // POST /api/auth/logout
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // POST /api/auth/logout-all
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->middleware('auth:sanctum');

    // POST /api/auth/refresh
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // GET /api/auth/me
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});


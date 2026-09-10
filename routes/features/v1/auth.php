<?php

use App\Features\Auth\Http\Controllers\AuthController;
use App\Features\Auth\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:password-reset')
    ->name('password.email');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:password-reset')
    ->name('password.update');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/me', [AuthController::class, 'me'])->name('my_profile');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:super-admin')
        ->name('users');
});

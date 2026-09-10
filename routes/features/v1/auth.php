<?php

use App\Features\Auth\Http\Controllers\AuthController;
use App\Features\Auth\Http\Controllers\UserController;
use App\Features\Settings\Http\Controllers\UserSettingsController;
use App\Features\Tenancy\Http\Controllers\MeController;
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

    Route::get('/me', [MeController::class, 'show'])->name('my_profile');

    Route::get('/me/settings', [UserSettingsController::class, 'show'])->name('me.settings.show');
    Route::patch('/me/settings', [UserSettingsController::class, 'update'])->name('me.settings.update');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:super-admin')
        ->name('users');
});

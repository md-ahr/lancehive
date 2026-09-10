<?php

use App\Features\Tenancy\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'freelancer.context'])
    ->group(function (): void {
        Route::get('/members', [MemberController::class, 'index'])
            ->name('members.index');

        Route::middleware('writable.subscription')->group(function (): void {
            Route::post('/members', [MemberController::class, 'store'])
                ->name('members.store');

            Route::delete('/members/{membership}', [MemberController::class, 'destroy'])
                ->name('members.destroy');
        });
    });

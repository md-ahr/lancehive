<?php

use App\Features\Delivery\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'freelancer.context'])
    ->group(function (): void {
        Route::get('/clients', [ClientController::class, 'index'])
            ->name('clients.index');

        Route::post('/clients', [ClientController::class, 'store'])
            ->name('clients.store');

        Route::get('/clients/{client}', [ClientController::class, 'show'])
            ->name('clients.show');

        Route::patch('/clients/{client}', [ClientController::class, 'update'])
            ->name('clients.update');

        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])
            ->name('clients.destroy');
    });

<?php

use App\Features\ClientPortal\Http\Controllers\PortalClientController;
use App\Features\ClientPortal\Http\Controllers\PortalClientInvoiceController;
use App\Features\ClientPortal\Http\Controllers\PortalProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'client.context'])
    ->prefix('portal')
    ->group(function (): void {
        Route::get('/client', [PortalClientController::class, 'show'])
            ->name('portal.client.show');

        Route::get('/projects', [PortalProjectController::class, 'index'])
            ->name('portal.projects.index');

        Route::get('/client-invoices', [PortalClientInvoiceController::class, 'index'])
            ->name('portal.client-invoices.index');
    });

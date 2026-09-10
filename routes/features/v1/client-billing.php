<?php

use App\Features\ClientBilling\Http\Controllers\ClientInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'freelancer.context'])
    ->group(function (): void {
        Route::get('/projects/{project}/client-invoices', [ClientInvoiceController::class, 'indexForProject'])
            ->name('projects.client-invoices.index');

        Route::post('/projects/{project}/client-invoices', [ClientInvoiceController::class, 'store'])
            ->name('projects.client-invoices.store');

        Route::get('/client-invoices/{clientInvoice}', [ClientInvoiceController::class, 'show'])
            ->name('client-invoices.show');

        Route::patch('/client-invoices/{clientInvoice}', [ClientInvoiceController::class, 'update'])
            ->name('client-invoices.update');

        Route::delete('/client-invoices/{clientInvoice}', [ClientInvoiceController::class, 'destroy'])
            ->name('client-invoices.destroy');

        Route::post('/client-invoices/{clientInvoice}/items', [ClientInvoiceController::class, 'storeItem'])
            ->name('client-invoices.items.store');

        Route::post('/client-invoices/{clientInvoice}/payments', [ClientInvoiceController::class, 'storePayment'])
            ->name('client-invoices.payments.store');
    });

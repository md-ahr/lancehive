<?php

use App\Features\Admin\Http\Controllers\FreelancerController;
use App\Features\Admin\Http\Controllers\FreelancerSubscriptionController;
use App\Features\Admin\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'can:super-admin'])
    ->group(function (): void {
        Route::get('/freelancers', [FreelancerController::class, 'index'])
            ->name('admin.freelancers.index');

        Route::post('/freelancers', [FreelancerController::class, 'store'])
            ->name('admin.freelancers.store');

        Route::get('/freelancers/{freelancer}', [FreelancerController::class, 'show'])
            ->name('admin.freelancers.show');

        Route::patch('/freelancers/{freelancer}', [FreelancerController::class, 'update'])
            ->name('admin.freelancers.update');

        Route::post('/freelancers/{freelancer}/resend-invite', [FreelancerController::class, 'resendInvite'])
            ->name('admin.freelancers.resend-invite');

        Route::patch('/freelancers/{freelancer}/subscription', [FreelancerSubscriptionController::class, 'update'])
            ->name('admin.freelancers.subscription.update');

        Route::get('/plans', [PlanController::class, 'index'])
            ->name('admin.plans.index');

        Route::post('/plans', [PlanController::class, 'store'])
            ->name('admin.plans.store');

        Route::patch('/plans/{plan}', [PlanController::class, 'update'])
            ->name('admin.plans.update');
    });

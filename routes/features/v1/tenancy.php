<?php

use App\Features\Settings\Http\Controllers\WorkspaceSettingsController;
use App\Features\Tenancy\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'freelancer.context'])
    ->group(function (): void {
        Route::get('/workspace/settings', [WorkspaceSettingsController::class, 'show'])
            ->name('workspace.settings.show');

        Route::get('/members', [MemberController::class, 'index'])
            ->name('members.index');

        Route::middleware(['writable.subscription', 'throttle:tenant-writes'])->group(function (): void {
            Route::patch('/workspace/settings', [WorkspaceSettingsController::class, 'update'])
                ->name('workspace.settings.update');

            Route::post('/members', [MemberController::class, 'store'])
                ->name('members.store');

            Route::delete('/members/{membership}', [MemberController::class, 'destroy'])
                ->name('members.destroy');
        });
    });

<?php

use App\Features\Reporting\Http\Controllers\AdminPlatformStatsController;
use App\Features\Reporting\Http\Controllers\AdminReportExportController;
use App\Features\Reporting\Http\Controllers\AdminRunReportController;
use App\Features\Reporting\Http\Controllers\AdminSavedReportController;
use App\Features\Reporting\Http\Controllers\ReportExportController;
use App\Features\Reporting\Http\Controllers\ReportExportDownloadController;
use App\Features\Reporting\Http\Controllers\RunReportController;
use App\Features\Reporting\Http\Controllers\SavedReportController;
use App\Features\Reporting\Http\Controllers\WorkspaceStatsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'freelancer.context'])->group(function (): void {
    Route::get('/workspace/stats', [WorkspaceStatsController::class, 'show'])
        ->name('workspace.stats');

    Route::post('/reports/run', [RunReportController::class, 'store'])
        ->middleware('throttle:reports')
        ->name('reports.run');

    Route::get('/reports', [SavedReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reports/{savedReport}', [SavedReportController::class, 'show'])
        ->whereNumber('savedReport')
        ->name('reports.show');

    Route::get('/report-exports', [ReportExportController::class, 'index'])
        ->name('report-exports.index');

    Route::get('/report-exports/{reportExport}', [ReportExportController::class, 'show'])
        ->whereNumber('reportExport')
        ->name('report-exports.show');

    Route::middleware(['writable.subscription', 'throttle:tenant-writes'])->group(function (): void {
        Route::post('/reports', [SavedReportController::class, 'store'])
            ->name('reports.store');

        Route::patch('/reports/{savedReport}', [SavedReportController::class, 'update'])
            ->whereNumber('savedReport')
            ->name('reports.update');

        Route::delete('/reports/{savedReport}', [SavedReportController::class, 'destroy'])
            ->whereNumber('savedReport')
            ->name('reports.destroy');

        Route::post('/report-exports', [ReportExportController::class, 'store'])
            ->middleware('throttle:reports')
            ->name('report-exports.store');
    });
});

Route::get('/report-exports/{export}/download', [ReportExportDownloadController::class, 'download'])
    ->middleware('signed')
    ->whereNumber('export')
    ->name('report-exports.download');

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'can:super-admin', 'throttle:admin'])
    ->group(function (): void {
        Route::get('/reports/platform-stats', [AdminPlatformStatsController::class, 'show'])
            ->name('admin.reports.platform-stats');

        Route::post('/reports/run', [AdminRunReportController::class, 'store'])
            ->middleware('throttle:reports')
            ->name('admin.reports.run');

        Route::get('/reports', [AdminSavedReportController::class, 'index'])
            ->name('admin.reports.index');

        Route::post('/reports', [AdminSavedReportController::class, 'store'])
            ->name('admin.reports.store');

        Route::get('/reports/{savedReport}', [AdminSavedReportController::class, 'show'])
            ->whereNumber('savedReport')
            ->name('admin.reports.show');

        Route::patch('/reports/{savedReport}', [AdminSavedReportController::class, 'update'])
            ->whereNumber('savedReport')
            ->name('admin.reports.update');

        Route::delete('/reports/{savedReport}', [AdminSavedReportController::class, 'destroy'])
            ->whereNumber('savedReport')
            ->name('admin.reports.destroy');

        Route::post('/report-exports', [AdminReportExportController::class, 'store'])
            ->middleware('throttle:reports')
            ->name('admin.report-exports.store');

        Route::get('/report-exports', [AdminReportExportController::class, 'index'])
            ->name('admin.report-exports.index');

        Route::get('/report-exports/{reportExport}', [AdminReportExportController::class, 'show'])
            ->whereNumber('reportExport')
            ->name('admin.report-exports.show');
    });

Route::get('/admin/report-exports/{export}/download', [ReportExportDownloadController::class, 'download'])
    ->middleware(['auth:sanctum', 'can:super-admin', 'signed'])
    ->whereNumber('export')
    ->name('admin.report-exports.download');

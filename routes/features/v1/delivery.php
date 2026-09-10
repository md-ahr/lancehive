<?php

use App\Features\Delivery\Http\Controllers\ClientController;
use App\Features\Delivery\Http\Controllers\ProjectController;
use App\Features\Delivery\Http\Controllers\TaskController;
use App\Features\Delivery\Http\Controllers\TimeLogController;
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

        Route::get('/clients/{client}/projects', [ProjectController::class, 'indexForClient'])
            ->name('clients.projects.index');

        Route::post('/clients/{client}/projects', [ProjectController::class, 'store'])
            ->name('clients.projects.store');

        Route::get('/projects', [ProjectController::class, 'index'])
            ->name('projects.index');

        Route::get('/projects/{project}', [ProjectController::class, 'show'])
            ->name('projects.show');

        Route::get('/projects/{project}/time-summary', [ProjectController::class, 'timeSummary'])
            ->name('projects.time-summary');

        Route::patch('/projects/{project}', [ProjectController::class, 'update'])
            ->name('projects.update');

        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
            ->name('projects.destroy');

        Route::get('/projects/{project}/tasks', [TaskController::class, 'indexForProject'])
            ->name('projects.tasks.index');

        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])
            ->name('projects.tasks.store');

        Route::get('/tasks/{task}', [TaskController::class, 'show'])
            ->name('tasks.show');

        Route::patch('/tasks/{task}', [TaskController::class, 'update'])
            ->name('tasks.update');

        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
            ->name('tasks.destroy');

        Route::get('/tasks/{task}/time-logs', [TimeLogController::class, 'indexForTask'])
            ->name('tasks.time-logs.index');

        Route::post('/tasks/{task}/time-logs', [TimeLogController::class, 'store'])
            ->name('tasks.time-logs.store');

        Route::patch('/time-logs/{timeLog}', [TimeLogController::class, 'update'])
            ->name('time-logs.update');

        Route::delete('/time-logs/{timeLog}', [TimeLogController::class, 'destroy'])
            ->name('time-logs.destroy');
    });

<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\TaskStatus;
use App\Features\Delivery\Http\Resources\TaskResource;
use App\Features\Delivery\Models\Task;

it('serializes task resource with expected keys and decimal estimated hours', function () {
    $task = Task::factory()->create([
        'title' => 'Homepage mockup',
        'status' => TaskStatus::InProgress,
        'due_date' => '2026-03-20',
        'estimated_hours' => '8.00',
    ]);

    $payload = (new TaskResource($task))->resolve();

    expect($payload)->toMatchArray([
        'id' => $task->id,
        'project_id' => $task->project_id,
        'title' => 'Homepage mockup',
        'status' => 'in_progress',
        'due_date' => '2026-03-20',
        'estimated_hours' => '8.00',
    ])
        ->and($payload)->toHaveKeys(['created_at', 'updated_at']);
});

it('serializes nullable due date and estimated hours as null', function () {
    $task = Task::factory()->create([
        'due_date' => null,
        'estimated_hours' => null,
    ]);

    $payload = (new TaskResource($task))->resolve();

    expect($payload['due_date'])->toBeNull()
        ->and($payload['estimated_hours'])->toBeNull();
});

it('formats estimated hours as a two-decimal string', function () {
    $task = Task::factory()->create(['estimated_hours' => 5]);

    $payload = (new TaskResource($task))->resolve();

    expect($payload['estimated_hours'])->toBe('5.00');
});

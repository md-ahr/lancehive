<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\TaskStatus;
use App\Features\Delivery\Models\Task;

it('exposes all task status values', function () {
    expect(TaskStatus::values())->toBe(['todo', 'in_progress', 'done']);
});

it('round trips task status through the model cast', function () {
    $task = Task::factory()->create(['status' => TaskStatus::InProgress]);

    expect($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->getAttributes()['status'])->toBe('in_progress');
});

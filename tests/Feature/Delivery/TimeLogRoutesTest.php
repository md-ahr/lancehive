<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;

it('denies unauthenticated access to time log routes', function () {
    $task = Task::factory()->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->getJson($this->apiUrl("tasks/{$task->id}/time-logs"))
        ->assertUnauthorized();

    $this->postJson($this->apiUrl("tasks/{$task->id}/time-logs"), [
        'hours' => '2.50',
        'logged_at' => '2026-03-08T09:00:00+00:00',
    ])
        ->assertUnauthorized();

    $this->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
        'hours' => '3.00',
    ])
        ->assertUnauthorized();

    $this->deleteJson($this->apiUrl("time-logs/{$timeLog->id}"))
        ->assertUnauthorized();
});

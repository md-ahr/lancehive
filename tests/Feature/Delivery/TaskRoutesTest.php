<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Task;

it('denies unauthenticated access to task routes', function () {
    $task = Task::factory()->create();

    $this->getJson($this->apiUrl("tasks/{$task->id}"))
        ->assertUnauthorized();
});

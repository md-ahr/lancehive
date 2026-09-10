<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;

it('belongs to task user and optional invoice item', function () {
    $timeLog = TimeLog::factory()->create();
    $task = Task::withoutGlobalScopes()->findOrFail($timeLog->task_id);
    $project = Project::withoutGlobalScopes()->findOrFail($task->project_id);
    test()->setTenantContext($project->freelancer_id);

    expect($timeLog->task)->not->toBeNull()
        ->and($timeLog->user)->not->toBeNull()
        ->and($timeLog->client_invoice_item_id)->toBeNull();
});

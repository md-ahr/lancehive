<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;

it('soft-deletes a task and hides it from the default list', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("tasks/{$task->id}"))
        ->assertOk()
        ->assertJsonPath('message', 'Task deleted successfully.');

    expect(Task::query()->find($task->id))->toBeNull()
        ->and(Task::withTrashed()->find($task->id))->not->toBeNull()
        ->and(TimeLog::withoutGlobalScopes()->whereKey($timeLog->id)->exists())->toBeTrue();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/tasks"))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns not found when deleting a task in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("tasks/{$task->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

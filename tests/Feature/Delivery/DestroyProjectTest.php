<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;

it('soft-deletes a project and hides it from the default list', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("projects/{$project->id}"))
        ->assertOk()
        ->assertJsonPath('message', 'Project deleted successfully.');

    expect(Project::query()->find($project->id))->toBeNull()
        ->and(Project::withTrashed()->find($project->id))->not->toBeNull()
        ->and(Task::withoutGlobalScopes()->whereKey($task->id)->exists())->toBeTrue();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('projects'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns not found when deleting a project in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("projects/{$project->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

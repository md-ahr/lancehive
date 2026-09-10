<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;

it('shows a task in the active workspace', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Homepage mockup',
        'estimated_hours' => '8.00',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("tasks/{$task->id}"))
        ->assertOk()
        ->assertJsonPath('id', $task->id)
        ->assertJsonPath('project_id', $project->id)
        ->assertJsonPath('title', 'Homepage mockup')
        ->assertJsonPath('estimated_hours', '8.00');
});

it('returns not found for a task in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("tasks/{$task->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('denies unauthenticated access to show task', function () {
    $task = Task::factory()->create();

    $this->getJson($this->apiUrl("tasks/{$task->id}"))
        ->assertUnauthorized();
});

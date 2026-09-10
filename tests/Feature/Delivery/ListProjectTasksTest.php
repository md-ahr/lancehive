<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;

it('returns paginated tasks scoped to a project', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $otherProject = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    Task::factory()->count(3)->for($project)->create();
    Task::factory()->for($otherProject)->create(['title' => 'Other Project Task']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/tasks?per_page=2"))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'project_id', 'title', 'status', 'due_date', 'estimated_hours', 'created_at', 'updated_at'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('data.0.project_id', $project->id)
        ->assertJsonMissing(['title' => 'Other Project Task']);
});

it('filters tasks by status', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    Task::factory()->for($project)->create(['title' => 'Todo Task']);
    Task::factory()->for($project)->inProgress()->create(['title' => 'In Progress Task']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/tasks?status=in_progress"))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'In Progress Task');
});

it('returns not found when listing tasks for another tenant project', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/tasks"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

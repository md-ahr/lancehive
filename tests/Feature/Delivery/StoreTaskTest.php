<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;

it('creates a task under a project for workspace member', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/tasks"), [
            'title' => 'Homepage mockup',
            'due_date' => '2026-03-20',
            'estimated_hours' => '8.00',
        ])
        ->assertCreated()
        ->assertJsonPath('title', 'Homepage mockup')
        ->assertJsonPath('project_id', $project->id)
        ->assertJsonPath('status', 'todo')
        ->assertJsonPath('due_date', '2026-03-20')
        ->assertJsonPath('estimated_hours', '8.00');

    expect(Task::query()->where('title', 'Homepage mockup')->exists())->toBeTrue();
});

it('returns validation error when title is missing', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/tasks"), [
            'estimated_hours' => '8.00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('returns not found when creating a task for another tenant project', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/tasks"), [
            'title' => 'Hijacked Task',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('denies unauthenticated task creation', function () {
    $project = Project::factory()->create();

    $this->postJson($this->apiUrl("projects/{$project->id}/tasks"), [
        'title' => 'Homepage mockup',
    ])
        ->assertUnauthorized();
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;

it('updates task status and fields partially', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Old Title',
        'status' => 'todo',
        'estimated_hours' => '8.00',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("tasks/{$task->id}"), [
            'title' => 'New Title',
            'status' => 'in_progress',
            'due_date' => '2026-03-20',
        ])
        ->assertOk()
        ->assertJsonPath('title', 'New Title')
        ->assertJsonPath('status', 'in_progress')
        ->assertJsonPath('estimated_hours', '8.00')
        ->assertJsonPath('due_date', '2026-03-20');

    expect($task->fresh()->status->value)->toBe('in_progress')
        ->and($task->fresh()->title)->toBe('New Title');
});

it('returns validation error for invalid status', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("tasks/{$task->id}"), [
            'status' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('returns not found when updating a task in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->patchJson($this->apiUrl("tasks/{$task->id}"), [
            'title' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

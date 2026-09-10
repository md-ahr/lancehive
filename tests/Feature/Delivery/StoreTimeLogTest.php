<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;

it('creates a time log for a task and sets user from auth', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("tasks/{$task->id}/time-logs"), [
            'hours' => '2.50',
            'description' => 'Initial wireframes',
            'logged_at' => '2026-03-08T09:00:00+00:00',
        ])
        ->assertCreated()
        ->assertJsonPath('task_id', $task->id)
        ->assertJsonPath('user_id', $workspace['user']->id)
        ->assertJsonPath('hours', '2.50')
        ->assertJsonPath('description', 'Initial wireframes');

    expect(TimeLog::query()->where('task_id', $task->id)->value('user_id'))
        ->toBe($workspace['user']->id);
});

it('returns validation error when hours is zero or negative', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("tasks/{$task->id}/time-logs"), [
            'hours' => '0',
            'logged_at' => '2026-03-08T09:00:00+00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hours']);
});

it('returns not found when logging time for another tenant task', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->postJson($this->apiUrl("tasks/{$task->id}/time-logs"), [
            'hours' => '2.50',
            'logged_at' => '2026-03-08T09:00:00+00:00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('denies unauthenticated time log creation', function () {
    $task = Task::factory()->create();

    $this->postJson($this->apiUrl("tasks/{$task->id}/time-logs"), [
        'hours' => '2.50',
        'logged_at' => '2026-03-08T09:00:00+00:00',
    ])
        ->assertUnauthorized();
});

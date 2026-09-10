<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

beforeEach(function () {
    $this->workspaceA = $this->createTenantWorkspace();
    $this->workspaceB = $this->createTenantWorkspace();
    $this->clientB = Client::factory()->for($this->workspaceB['freelancer'])->create();
    $this->projectB = Project::factory()->for($this->clientB)->for($this->workspaceB['freelancer'])->create();
    $this->taskB = Task::factory()->for($this->projectB)->create();
    $this->timeLogB = TimeLog::factory()->for($this->taskB)->create();
});

it('returns not found when showing another tenants task', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("tasks/{$this->taskB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when updating another tenants task', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->patchJson($this->apiUrl("tasks/{$this->taskB->id}"), [
            'title' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when deleting another tenants task', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("tasks/{$this->taskB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when creating a task on another tenants project', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->postJson($this->apiUrl("projects/{$this->projectB->id}/tasks"), [
            'title' => 'Cross Tenant Task',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when logging time on another tenants task', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->postJson($this->apiUrl("tasks/{$this->taskB->id}/time-logs"), [
            'hours' => '1.00',
            'logged_at' => '2026-03-08T09:00:00+00:00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('forbids a member from updating another users time log in the same tenant', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
            'hours' => '3.00',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('returns not found when updating another tenants time log', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$this->timeLogB->id}"), [
            'hours' => '5.00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when loading time summary for another tenants project', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$this->projectB->id}/time-summary"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

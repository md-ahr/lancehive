<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

it('updates project fields partially without requiring hourly rate', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create([
        'name' => 'Old Name',
        'hourly_rate' => '1500.00',
        'status' => 'active',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("projects/{$project->id}"), [
            'name' => 'New Name',
            'status' => 'on_hold',
            'deadline' => '2026-12-31',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'New Name')
        ->assertJsonPath('status', 'on_hold')
        ->assertJsonPath('hourly_rate', '1500.00')
        ->assertJsonPath('deadline', '2026-12-31');

    expect($project->fresh()->name)->toBe('New Name')
        ->and($project->fresh()->hourly_rate)->toBe('1500.00');
});

it('returns validation error for invalid status', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("projects/{$project->id}"), [
            'status' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('returns not found when updating a project in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->patchJson($this->apiUrl("projects/{$project->id}"), [
            'name' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

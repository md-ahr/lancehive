<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

beforeEach(function () {
    $this->workspaceA = $this->createTenantWorkspace();
    $this->workspaceB = $this->createTenantWorkspace();
    $this->clientA = Client::factory()->for($this->workspaceA['freelancer'])->create();
    $this->clientB = Client::factory()->for($this->workspaceB['freelancer'])->create();
    $this->projectA = Project::factory()->for($this->clientA)->for($this->workspaceA['freelancer'])->create(['name' => 'Tenant A Project']);
    $this->projectB = Project::factory()->for($this->clientB)->for($this->workspaceB['freelancer'])->create(['name' => 'Tenant B Project']);
});

it('lists only projects from the active workspace', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl('projects'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Tenant A Project');
});

it('returns not found when showing another tenants project', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$this->projectB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when creating a project for another tenants client', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->postJson($this->apiUrl("clients/{$this->clientB->id}/projects"), [
            'name' => 'Cross Tenant Project',
            'hourly_rate' => '100.00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when listing projects for another tenants client', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("clients/{$this->clientB->id}/projects"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when updating a project from another tenant via flat route', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->patchJson($this->apiUrl("projects/{$this->projectB->id}"), [
            'name' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when deleting a project from another tenant', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("projects/{$this->projectB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

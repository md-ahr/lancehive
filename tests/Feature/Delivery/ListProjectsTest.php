<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

it('returns paginated tenant projects with cursor meta', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    Project::factory()->count(3)->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('projects?per_page=2'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'client_id', 'name', 'hourly_rate', 'currency', 'status', 'deadline', 'created_at', 'updated_at'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2);
});

it('only lists projects from the active workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $clientA = Client::factory()->for($workspaceA['freelancer'])->create();
    $clientB = Client::factory()->for($workspaceB['freelancer'])->create();
    Project::factory()->for($clientA)->for($workspaceA['freelancer'])->create(['name' => 'Workspace A Project']);
    Project::factory()->for($clientB)->for($workspaceB['freelancer'])->create(['name' => 'Workspace B Project']);

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl('projects'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Workspace A Project');
});

it('excludes soft-deleted projects from the default list', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $live = Project::factory()->for($client)->for($workspace['freelancer'])->create(['name' => 'Live Project']);
    $deleted = Project::factory()->for($client)->for($workspace['freelancer'])->create(['name' => 'Deleted Project']);
    $deleted->delete();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('projects'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $live->id)
        ->assertJsonPath('data.0.name', 'Live Project');
});

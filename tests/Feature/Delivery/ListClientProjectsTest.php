<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

it('returns paginated projects scoped to a client', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $otherClient = Client::factory()->for($workspace['freelancer'])->create();
    Project::factory()->count(3)->for($client)->for($workspace['freelancer'])->create();
    Project::factory()->for($otherClient)->for($workspace['freelancer'])->create(['name' => 'Other Client Project']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}/projects?per_page=2"))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'client_id', 'name', 'hourly_rate', 'currency', 'status', 'deadline', 'created_at', 'updated_at'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('data.0.client_id', $client->id)
        ->assertJsonMissing(['name' => 'Other Client Project']);
});

it('lists projects for an archived client', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->archived()->create();
    Project::factory()->for($client)->for($workspace['freelancer'])->create(['name' => 'Still Listed']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}/projects"))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Still Listed');
});

it('returns not found when listing projects for another tenant client', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}/projects"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

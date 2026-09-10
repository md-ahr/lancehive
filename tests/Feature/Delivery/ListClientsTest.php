<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;

it('returns paginated clients for workspace member', function () {
    $workspace = $this->createTenantWorkspace();
    Client::factory()->count(3)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('clients?per_page=2'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'status', 'contact_email', 'created_at', 'updated_at'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2);
});

it('filters clients by status', function () {
    $workspace = $this->createTenantWorkspace();
    Client::factory()->for($workspace['freelancer'])->create(['name' => 'Active Co']);
    Client::factory()->for($workspace['freelancer'])->archived()->create(['name' => 'Archived Co']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('clients?status=archived'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Archived Co')
        ->assertJsonPath('data.0.status', 'archived');
});

it('returns empty list with valid meta when tenant has no clients', function () {
    $this->actingAsTenant()
        ->getJson($this->apiUrl('clients'))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ]);
});

it('only lists clients from active workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    Client::factory()->for($workspaceA['freelancer'])->create(['name' => 'Workspace A Client']);
    Client::factory()->for($workspaceB['freelancer'])->create(['name' => 'Workspace B Client']);

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl('clients'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Workspace A Client');
});

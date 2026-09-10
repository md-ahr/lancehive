<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;

beforeEach(function () {
    $this->workspaceA = $this->createTenantWorkspace();
    $this->workspaceB = $this->createTenantWorkspace();
    $this->clientA = Client::factory()->for($this->workspaceA['freelancer'])->create(['name' => 'Tenant A Client']);
    $this->clientB = Client::factory()->for($this->workspaceB['freelancer'])->create(['name' => 'Tenant B Client']);
});

it('lists only clients from the active workspace', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl('clients'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Tenant A Client');
});

it('returns not found when showing another tenants client', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("clients/{$this->clientB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when updating another tenants client', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->patchJson($this->apiUrl("clients/{$this->clientB->id}"), [
            'name' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when deleting another tenants client', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("clients/{$this->clientB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('creates clients under the active workspace only', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'New Tenant A Client'])
        ->assertCreated();

    $created = Client::query()->where('name', 'New Tenant A Client')->firstOrFail();

    expect($created->freelancer_id)->toBe($this->workspaceA['freelancer']->id)
        ->and(Client::withoutGlobalScopes()->where('freelancer_id', $this->workspaceB['freelancer']->id)->count())->toBe(1);
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;

it('updates client fields partially', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create([
        'name' => 'Old Name',
        'contact_email' => 'old@example.com',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("clients/{$client->id}"), [
            'name' => 'New Name',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'New Name')
        ->assertJsonPath('contact_email', 'old@example.com');

    expect($client->fresh()->name)->toBe('New Name');
});

it('allows empty patch body as no-op', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create([
        'name' => 'Unchanged Co',
        'contact_email' => 'same@example.com',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("clients/{$client->id}"), [])
        ->assertOk()
        ->assertJsonPath('name', 'Unchanged Co')
        ->assertJsonPath('contact_email', 'same@example.com');
});

it('returns validation error for invalid status', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("clients/{$client->id}"), [
            'status' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('returns not found when updating a client in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->patchJson($this->apiUrl("clients/{$client->id}"), [
            'name' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

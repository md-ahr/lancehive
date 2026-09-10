<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;

it('shows a client in the active workspace', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create([
        'name' => 'BigCo Ltd',
        'contact_email' => 'billing@bigco.com',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}"))
        ->assertOk()
        ->assertJsonPath('id', $client->id)
        ->assertJsonPath('name', 'BigCo Ltd')
        ->assertJsonPath('contact_email', 'billing@bigco.com');
});

it('shows archived clients', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->archived()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}"))
        ->assertOk()
        ->assertJsonPath('status', 'archived');
});

it('returns not found for a client in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('denies unauthenticated access to show client', function () {
    $client = Client::factory()->create();

    $this->getJson($this->apiUrl("clients/{$client->id}"))
        ->assertUnauthorized();
});

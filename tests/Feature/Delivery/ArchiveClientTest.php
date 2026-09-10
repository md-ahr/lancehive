<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

it('archives a client without deleting projects', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("clients/{$client->id}"))
        ->assertOk()
        ->assertJsonPath('message', 'Client archived successfully.');

    expect($client->fresh()->status)->toBe(ClientStatus::Archived)
        ->and(Project::query()->find($project->id))->not->toBeNull();
});

it('is idempotent when archiving an already archived client', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->archived()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("clients/{$client->id}"))
        ->assertOk()
        ->assertJsonPath('message', 'Client archived successfully.');

    expect($client->fresh()->status)->toBe(ClientStatus::Archived);
});

it('returns not found when archiving a client in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("clients/{$client->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

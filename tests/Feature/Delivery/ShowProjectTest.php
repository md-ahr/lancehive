<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

it('shows a project in the active workspace', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create([
        'name' => 'Website Redesign',
        'hourly_rate' => '1500.00',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}"))
        ->assertOk()
        ->assertJsonPath('id', $project->id)
        ->assertJsonPath('client_id', $client->id)
        ->assertJsonPath('name', 'Website Redesign')
        ->assertJsonPath('hourly_rate', '1500.00');
});

it('returns not found for a project in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('denies unauthenticated access to show project', function () {
    $project = Project::factory()->create();

    $this->getJson($this->apiUrl("projects/{$project->id}"))
        ->assertUnauthorized();
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('denies unauthenticated access to client invoice routes', function () {
    $this->getJson($this->apiUrl('client-invoices/1'))
        ->assertUnauthorized();
});

it('denies members from creating invoices', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/client-invoices"), [])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

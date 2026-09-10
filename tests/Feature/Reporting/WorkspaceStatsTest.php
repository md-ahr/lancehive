<?php

declare(strict_types=1);

use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('returns workspace stats for a member', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('workspace/stats'))
        ->assertOk()
        ->assertJsonStructure([
            'active_clients',
            'active_projects',
            'hours_this_month',
            'unbilled_hours',
            'outstanding_invoice_total',
        ]);
});

it('requires authentication for workspace stats', function () {
    $this->getJson($this->apiUrl('workspace/stats'))
        ->assertUnauthorized();
});

it('returns forbidden for cross tenant workspace header mismatch', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->withHeader('X-Freelancer-Id', (string) $workspaceB['freelancer']->id)
        ->getJson($this->apiUrl('workspace/stats'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

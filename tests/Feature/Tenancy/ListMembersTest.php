<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;

it('returns paginated workspace members with user details', function () {
    $workspace = $this->createTenantWorkspace();
    $memberUser = User::factory()->create(['name' => 'Team Member']);
    FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for($memberUser)
        ->state(['role' => FreelancerMembershipRole::Member])
        ->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('members'))
        ->assertOk()
        ->assertJsonPath('data.0.user.email', $workspace['user']->email)
        ->assertJsonPath('data.1.user.name', 'Team Member');
});

it('allows workspace members to list team members', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('members'))
        ->assertOk();
});

it('denies unauthenticated member listing', function () {
    $this->getJson($this->apiUrl('members'))
        ->assertUnauthorized();
});

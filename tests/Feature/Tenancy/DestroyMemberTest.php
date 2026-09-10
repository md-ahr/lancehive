<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;

it('removes a workspace member for owner', function () {
    $workspace = $this->createTenantWorkspace();
    $memberUser = User::factory()->create();
    $membership = FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for($memberUser)
        ->state(['role' => FreelancerMembershipRole::Member])
        ->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl('members/'.$membership->id))
        ->assertOk()
        ->assertJsonPath('message', 'Workspace member removed successfully.');

    expect(FreelancerMembership::query()->find($membership->id))->toBeNull();
});

it('prevents admin from removing another admin', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    $otherAdmin = User::factory()->create();
    $membership = FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for($otherAdmin)
        ->state(['role' => FreelancerMembershipRole::Admin])
        ->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl('members/'.$membership->id))
        ->assertForbidden();
});

it('prevents removing the workspace owner', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl('members/'.$workspace['membership']->id))
        ->assertForbidden();
});

it('returns not found for membership in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $foreignMember = User::factory()->create();
    $membership = FreelancerMembership::factory()
        ->for($workspaceB['freelancer'])
        ->for($foreignMember)
        ->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->deleteJson($this->apiUrl('members/'.$membership->id))
        ->assertNotFound();
});

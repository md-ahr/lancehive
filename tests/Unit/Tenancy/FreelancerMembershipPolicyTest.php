<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;
use App\Features\Tenancy\Policies\FreelancerMembershipPolicy;

it('allows owners and admins to invite members', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    test()->setTenantContext($workspace['freelancer']);

    $policy = new FreelancerMembershipPolicy;

    expect($policy->create($workspace['user']))->toBeTrue();
});

it('denies members from inviting teammates', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);

    $policy = new FreelancerMembershipPolicy;

    expect($policy->create($workspace['user']))->toBeFalse();
});

it('allows owner to remove admins and members but not owners', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);

    $admin = FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for(User::factory()->create())
        ->state(['role' => FreelancerMembershipRole::Admin])
        ->create();

    $policy = new FreelancerMembershipPolicy;

    expect($policy->delete($workspace['user'], $admin))->toBeTrue()
        ->and($policy->delete($workspace['user'], $workspace['membership']))->toBeFalse();
});

it('allows admin to remove members only', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    test()->setTenantContext($workspace['freelancer']);

    $member = FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for(User::factory()->create())
        ->state(['role' => FreelancerMembershipRole::Member])
        ->create();
    $otherAdmin = FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for(User::factory()->create())
        ->state(['role' => FreelancerMembershipRole::Admin])
        ->create();

    $policy = new FreelancerMembershipPolicy;

    expect($policy->delete($workspace['user'], $member))->toBeTrue()
        ->and($policy->delete($workspace['user'], $otherAdmin))->toBeFalse();
});

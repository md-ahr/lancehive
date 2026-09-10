<?php

declare(strict_types=1);

use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;

it('creates a tenant workspace with owner membership', function () {
    $workspace = $this->createTenantWorkspace();

    expect($workspace['freelancer']->owner_user_id)->toBe($workspace['user']->id);
    expect($workspace['membership']->role)->toBe(FreelancerMembershipRole::Owner);
    expect($workspace['membership']->freelancer_id)->toBe($workspace['freelancer']->id);
    expect($workspace['membership']->user_id)->toBe($workspace['user']->id);

    expect(FreelancerMembership::query()->count())->toBe(1);
});

it('authenticates via actingAsTenant with an existing workspace', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.id', $workspace['user']->id);
});

it('creates and authenticates a workspace when actingAsTenant is called with no arguments', function () {
    $this->actingAsTenant()
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role']]);
});

it('supports non-owner membership roles via createTenantWorkspace', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    expect($workspace['membership']->role)->toBe(FreelancerMembershipRole::Member);
});

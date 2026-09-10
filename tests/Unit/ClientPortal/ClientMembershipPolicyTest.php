<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Policies\ClientMembershipPolicy;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows workspace admins to invite client members', function () {
    $workspace = test()->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    test()->setTenantContext($workspace['freelancer']);

    $policy = new ClientMembershipPolicy;

    expect($policy->create($workspace['user'], $client))->toBeTrue();
});

it('denies workspace members from inviting client members', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    test()->setTenantContext($workspace['freelancer']);

    $policy = new ClientMembershipPolicy;

    expect($policy->create($workspace['user'], $client))->toBeFalse();
});

it('allows client portal users to view portal resources', function () {
    $portal = test()->createClientPortalUser();
    test()->setClientContext($portal['client']);

    $policy = new ClientMembershipPolicy;

    expect($policy->viewAny($portal['user']))->toBeTrue();
});

it('denies non-members from viewing portal resources', function () {
    $portal = test()->createClientPortalUser();
    test()->setClientContext($portal['client']);

    $policy = new ClientMembershipPolicy;

    expect($policy->viewAny(User::factory()->create()))->toBeFalse();
});

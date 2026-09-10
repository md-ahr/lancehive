<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Policies\ClientPolicy;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows members to view clients but not mutate them', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $policy = new ClientPolicy;

    expect($policy->view($workspace['user'], $client))->toBeTrue()
        ->and($policy->create($workspace['user']))->toBeFalse()
        ->and($policy->update($workspace['user'], $client))->toBeFalse()
        ->and($policy->delete($workspace['user'], $client))->toBeFalse();
});

it('allows owners and admins to manage clients', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    test()->setTenantContext($workspace['freelancer']);
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $policy = new ClientPolicy;

    expect($policy->create($workspace['user']))->toBeTrue()
        ->and($policy->update($workspace['user'], $client))->toBeTrue()
        ->and($policy->delete($workspace['user'], $client))->toBeTrue();
});

it('allows super admin to manage clients in tenant context', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $admin = User::factory()->superAdmin()->create();

    $policy = new ClientPolicy;

    expect($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $client))->toBeTrue()
        ->and($policy->delete($admin, $client))->toBeTrue();
});

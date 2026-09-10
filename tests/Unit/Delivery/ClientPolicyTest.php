<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Policies\ClientPolicy;

it('allows workspace members to manage clients', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $policy = new ClientPolicy;

    expect($policy->viewAny($workspace['user']))->toBeTrue()
        ->and($policy->view($workspace['user'], $client))->toBeTrue()
        ->and($policy->create($workspace['user']))->toBeTrue()
        ->and($policy->update($workspace['user'], $client))->toBeTrue()
        ->and($policy->delete($workspace['user'], $client))->toBeTrue();
});

it('denies non-members', function () {
    $workspace = test()->createTenantWorkspace();
    $outsider = test()->createTenantWorkspace()['user'];
    $client = Client::factory()->for($workspace['freelancer'])->create();

    test()->setTenantContext($workspace['freelancer']);

    $policy = new ClientPolicy;

    expect($policy->view($outsider, $client))->toBeFalse();
});

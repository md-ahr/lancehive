<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Policies\ProjectPolicy;
use App\Features\Tenancy\Models\Freelancer;

it('allows members to manage tenant projects', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);
    $project = Project::factory()->for($workspace['freelancer'])->create();

    $policy = new ProjectPolicy;

    expect($policy->create($workspace['user']))->toBeTrue()
        ->and($policy->update($workspace['user'], $project))->toBeTrue();
});

it('denies creating a project for a client outside the tenant', function () {
    $workspace = test()->createTenantWorkspace();
    $foreignClient = Client::factory()->for(Freelancer::factory()->active()->create())->create();

    test()->setTenantContext($workspace['freelancer']);

    $policy = new ProjectPolicy;

    expect($policy->create($workspace['user'], $foreignClient))->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Policies\FreelancerPolicy;

it('allows members to view their active workspace', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);

    $policy = new FreelancerPolicy;

    expect($policy->view($workspace['user'], $workspace['freelancer']))->toBeTrue();
});

it('denies viewing another workspace', function () {
    $workspace = test()->createTenantWorkspace();
    $otherFreelancer = Freelancer::factory()->active()->create();

    test()->setTenantContext($workspace['freelancer']);

    $policy = new FreelancerPolicy;

    expect($policy->view($workspace['user'], $otherFreelancer))->toBeFalse();
});

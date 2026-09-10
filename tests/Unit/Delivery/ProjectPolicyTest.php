<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Policies\ProjectPolicy;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows members to view projects but not mutate them', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($workspace['freelancer'])->for($client)->create();

    $policy = new ProjectPolicy;

    expect($policy->view($workspace['user'], $project))->toBeTrue()
        ->and($policy->create($workspace['user'], $client))->toBeFalse()
        ->and($policy->update($workspace['user'], $project))->toBeFalse()
        ->and($policy->delete($workspace['user'], $project))->toBeFalse();
});

it('allows owners and admins to manage projects', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    test()->setTenantContext($workspace['freelancer']);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($workspace['freelancer'])->for($client)->create();

    $policy = new ProjectPolicy;

    expect($policy->create($workspace['user'], $client))->toBeTrue()
        ->and($policy->update($workspace['user'], $project))->toBeTrue()
        ->and($policy->delete($workspace['user'], $project))->toBeTrue();
});

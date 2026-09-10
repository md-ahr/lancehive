<?php

declare(strict_types=1);

use App\Features\Reporting\Models\SavedReport;
use App\Features\Reporting\Policies\SavedReportPolicy;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows members to view saved reports', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $this->setTenantContext($workspace['freelancer']);
    $report = SavedReport::factory()->for($workspace['freelancer'])->create();
    $policy = new SavedReportPolicy;

    expect($policy->view($workspace['user'], $report))->toBeTrue();
});

it('denies members from creating saved reports', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $this->setTenantContext($workspace['freelancer']);
    $policy = new SavedReportPolicy;

    expect($policy->create($workspace['user']))->toBeFalse();
});

it('allows owners to create saved reports', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Owner);
    $this->setTenantContext($workspace['freelancer']);
    $policy = new SavedReportPolicy;

    expect($policy->create($workspace['user']))->toBeTrue();
});

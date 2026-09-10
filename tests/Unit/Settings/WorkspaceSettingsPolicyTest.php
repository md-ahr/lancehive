<?php

declare(strict_types=1);

use App\Features\Settings\Policies\WorkspaceSettingsPolicy;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows members to view workspace settings', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);

    $policy = new WorkspaceSettingsPolicy;

    expect($policy->viewAny($workspace['user']))->toBeTrue();
});

it('denies members from updating workspace settings', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);

    $policy = new WorkspaceSettingsPolicy;

    expect($policy->updateAny($workspace['user']))->toBeFalse();
});

it('allows owners to update workspace settings', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);

    $policy = new WorkspaceSettingsPolicy;

    expect($policy->updateAny($workspace['user']))->toBeTrue();
});

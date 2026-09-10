<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Delivery\Policies\TimeLogPolicy;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows members to edit only their own time logs', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);

    $ownLog = TimeLog::factory()->create(['user_id' => $workspace['user']->id]);
    $otherLog = TimeLog::factory()->create(['user_id' => User::factory()->create()->id]);

    $policy = new TimeLogPolicy;

    expect($policy->update($workspace['user'], $ownLog))->toBeTrue()
        ->and($policy->update($workspace['user'], $otherLog))->toBeFalse();
});

it('allows owners and admins to edit any time log in the workspace', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    test()->setTenantContext($workspace['freelancer']);

    $otherUsersLog = TimeLog::factory()->create(['user_id' => User::factory()->create()->id]);

    $policy = new TimeLogPolicy;

    expect($policy->update($workspace['user'], $otherUsersLog))->toBeTrue();
});

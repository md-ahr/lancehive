<?php

declare(strict_types=1);

use App\Core\Http\Exceptions\ApiException;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;
use App\Features\Tenancy\Services\TeamMemberInviteService;
use Illuminate\Validation\ValidationException;

it('creates membership for a new user', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);

    $membership = app(TeamMemberInviteService::class)->invite($workspace['freelancer']->id, [
        'name' => 'Invited User',
        'email' => 'invited@workspace.test',
        'role' => FreelancerMembershipRole::Member,
    ]);

    expect($membership->role)->toBe(FreelancerMembershipRole::Member)
        ->and($membership->user->email)->toBe('invited@workspace.test');
});

it('throws when inviting an existing workspace member', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);
    $existingUser = User::factory()->create(['email' => 'member@workspace.test']);
    FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for($existingUser)
        ->create();

    expect(fn () => app(TeamMemberInviteService::class)->invite($workspace['freelancer']->id, [
        'name' => 'Member',
        'email' => 'member@workspace.test',
        'role' => FreelancerMembershipRole::Member,
    ]))->toThrow(ValidationException::class);
});

it('throws when team member limit is reached', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);
    $plan = Plan::factory()->create(['max_team_members' => 1]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    expect(fn () => app(TeamMemberInviteService::class)->invite($workspace['freelancer']->id, [
        'name' => 'Over Limit',
        'email' => 'over.limit@workspace.test',
        'role' => FreelancerMembershipRole::Member,
    ]))->toThrow(ApiException::class, 'Team member limit reached for your plan.');
});

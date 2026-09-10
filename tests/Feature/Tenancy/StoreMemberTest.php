<?php

declare(strict_types=1);

use App\Features\Admin\Notifications\FreelancerInviteNotification;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;
use App\Features\Tenancy\Notifications\WorkspaceMemberAddedNotification;
use Illuminate\Support\Facades\Notification;

it('invites a new workspace member for owner', function () {
    Notification::fake();

    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('members'), [
            'name' => 'New Member',
            'email' => 'new.member@workspace.test',
            'role' => 'member',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'member')
        ->assertJsonPath('user.email', 'new.member@workspace.test');

    $invitedUser = User::query()->where('email', 'new.member@workspace.test')->firstOrFail();
    expect(FreelancerMembership::query()->where('user_id', $invitedUser->id)->exists())->toBeTrue();

    Notification::assertSentTo($invitedUser, FreelancerInviteNotification::class);
});

it('adds an existing user to the workspace', function () {
    Notification::fake();

    $workspace = $this->createTenantWorkspace();
    $existingUser = User::factory()->create([
        'name' => 'Existing User',
        'email' => 'existing@workspace.test',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('members'), [
            'name' => 'Ignored Name',
            'email' => 'existing@workspace.test',
            'role' => 'admin',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'admin')
        ->assertJsonPath('user.name', 'Existing User');

    Notification::assertSentTo($existingUser, WorkspaceMemberAddedNotification::class);
});

it('denies member role from inviting teammates', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('members'), [
            'name' => 'Blocked Invite',
            'email' => 'blocked@workspace.test',
            'role' => 'member',
        ])
        ->assertForbidden();
});

it('returns validation error when user is already a member', function () {
    $workspace = $this->createTenantWorkspace();
    $existingMember = User::factory()->create(['email' => 'already@workspace.test']);
    FreelancerMembership::factory()
        ->for($workspace['freelancer'])
        ->for($existingMember)
        ->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('members'), [
            'name' => 'Already Member',
            'email' => 'already@workspace.test',
            'role' => 'member',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns plan limit exceeded when team member cap is reached', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_team_members' => 1]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('members'), [
            'name' => 'Over Limit',
            'email' => 'over.limit@workspace.test',
            'role' => 'member',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'plan_limit_exceeded');
});

it('rejects owner role in invite payload', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('members'), [
            'name' => 'Fake Owner',
            'email' => 'fake.owner@workspace.test',
            'role' => 'owner',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

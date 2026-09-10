<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\ClientPortal\Notifications\ClientPortalInviteNotification;
use App\Features\ClientPortal\Notifications\ClientPortalMemberAddedNotification;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use Illuminate\Support\Facades\Notification;

it('invites a new client portal member for workspace admin', function () {
    Notification::fake();

    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/members"), [
            'name' => 'Portal Viewer',
            'email' => 'portal.viewer@client.test',
            'role' => 'viewer',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'viewer')
        ->assertJsonPath('user.email', 'portal.viewer@client.test');

    $invitedUser = User::query()->where('email', 'portal.viewer@client.test')->firstOrFail();
    expect(ClientMembership::query()->where('user_id', $invitedUser->id)->exists())->toBeTrue();

    Notification::assertSentTo($invitedUser, ClientPortalInviteNotification::class);
});

it('adds an existing user to the client portal', function () {
    Notification::fake();

    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $existingUser = User::factory()->create([
        'name' => 'Existing Portal User',
        'email' => 'existing.portal@client.test',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/members"), [
            'name' => 'Ignored Name',
            'email' => 'existing.portal@client.test',
            'role' => 'primary',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'primary')
        ->assertJsonPath('user.name', 'Existing Portal User');

    Notification::assertSentTo($existingUser, ClientPortalMemberAddedNotification::class);
});

it('denies workspace member from inviting client portal users', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/members"), [
            'name' => 'Blocked Invite',
            'email' => 'blocked@client.test',
            'role' => 'viewer',
        ])
        ->assertForbidden();
});

it('returns validation error when user is already a client member', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $existingMember = User::factory()->create(['email' => 'already@client.test']);
    ClientMembership::factory()->for($client)->for($existingMember)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/members"), [
            'name' => 'Already Member',
            'email' => 'already@client.test',
            'role' => ClientMembershipRole::Viewer->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns not found when inviting to a foreign client', function () {
    $workspace = $this->createTenantWorkspace();
    $foreignClient = Client::factory()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$foreignClient->id}/members"), [
            'name' => 'Foreign Invite',
            'email' => 'foreign@client.test',
            'role' => 'viewer',
        ])
        ->assertNotFound();
});

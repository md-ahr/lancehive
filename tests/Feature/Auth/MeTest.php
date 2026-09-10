<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Cache\MembershipCache;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to me endpoint', function () {
    $this->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

it('allows authenticated freelancer to access me endpoint', function () {
    $freelancer = User::factory()->freelancer()->create([
        'email' => 'freelancer@example.com',
    ]);

    Sanctum::actingAs($freelancer);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.id', $freelancer->id)
        ->assertJsonPath('user.email', 'freelancer@example.com')
        ->assertJsonPath('user.role', UserRole::User->value)
        ->assertJsonPath('user_settings.timezone', 'UTC')
        ->assertJsonPath('user_settings.locale', 'en')
        ->assertJsonPath('memberships', [])
        ->assertJsonPath('active_freelancer', null)
        ->assertJsonPath('subscription', null);
});

it('allows authenticated client to access me endpoint', function () {
    $client = User::factory()->client()->create([
        'email' => 'client@example.com',
    ]);

    Sanctum::actingAs($client);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.id', $client->id)
        ->assertJsonPath('user.email', 'client@example.com')
        ->assertJsonPath('user.role', UserRole::User->value)
        ->assertJsonPath('memberships', []);
});

it('allows me endpoint access with bearer token from login', function () {
    User::factory()->client()->create([
        'email' => 'client@example.com',
        'password' => 'password',
    ]);

    $token = $this->postJson($this->apiUrl('login'), [
        'email' => 'client@example.com',
        'password' => 'password',
    ])->json('token');

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.email', 'client@example.com')
        ->assertJsonPath('user.role', UserRole::User->value);
});

it('returns memberships active workspace and subscription summary', function () {
    Cache::flush();

    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['name' => 'Starter']);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->create();

    Sanctum::actingAs($workspace['user']);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'memberships' => [
                '*' => [
                    'id',
                    'freelancer_id',
                    'user_id',
                    'role',
                    'freelancer' => ['id', 'name', 'slug', 'status'],
                ],
            ],
            'active_freelancer' => ['id', 'name', 'slug', 'status'],
            'subscription' => ['status', 'plan_name', 'read_only', 'trial_ends_at'],
            'client_memberships' => [],
            'active_client',
        ])
        ->assertJsonPath('memberships.0.freelancer_id', $workspace['freelancer']->id)
        ->assertJsonPath('active_freelancer.id', $workspace['freelancer']->id)
        ->assertJsonPath('subscription.plan_name', 'Starter')
        ->assertJsonPath('subscription.status', 'trialing');

    expect(Cache::has('memberships:user:'.$workspace['user']->id))->toBeTrue();

    app(MembershipCache::class)->forget($workspace['user']->id);
});

it('returns null active workspace when user belongs to multiple workspaces without header', function () {
    $user = User::factory()->freelancer()->create();
    $first = Freelancer::factory()->active()->create(['owner_user_id' => $user->id]);
    $second = Freelancer::factory()->active()->create();

    FreelancerMembership::factory()->for($first)->for($user)->owner()->create();
    FreelancerMembership::factory()->for($second)->for($user)->member()->create();

    Sanctum::actingAs($user);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonCount(2, 'memberships')
        ->assertJsonPath('active_freelancer', null)
        ->assertJsonPath('subscription', null);
});

it('returns client memberships and active client summary', function () {
    Cache::flush();

    $portal = $this->createClientPortalUser();

    Sanctum::actingAs($portal['user']);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonCount(1, 'client_memberships')
        ->assertJsonPath('client_memberships.0.client_id', $portal['client']->id)
        ->assertJsonPath('active_client.id', $portal['client']->id)
        ->assertJsonPath('memberships', []);
});

it('returns null active client when user belongs to multiple client organizations without header', function () {
    $user = User::factory()->create();
    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    ClientMembership::factory()->for($firstClient)->for($user)->create();
    ClientMembership::factory()->for($secondClient)->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonCount(2, 'client_memberships')
        ->assertJsonPath('active_client', null);
});

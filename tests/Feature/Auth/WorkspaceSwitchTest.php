<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Laravel\Sanctum\Sanctum;

it('returns active freelancer and subscription for the selected workspace header', function () {
    $user = User::factory()->freelancer()->create();
    $first = Freelancer::factory()->active()->create(['owner_user_id' => $user->id, 'name' => 'Studio A']);
    $second = Freelancer::factory()->active()->create(['name' => 'Studio B']);
    $plan = Plan::factory()->create(['name' => 'Starter']);

    FreelancerMembership::factory()->for($first)->for($user)->owner()->create();
    FreelancerMembership::factory()->for($second)->for($user)->member()->create();
    Subscription::factory()->for($second)->for($plan)->create();

    Sanctum::actingAs($user);

    $this->withHeader('X-Freelancer-Id', (string) $second->id)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('active_freelancer.id', $second->id)
        ->assertJsonPath('active_freelancer.name', 'Studio B')
        ->assertJsonPath('subscription.plan_name', 'Starter')
        ->assertJsonCount(2, 'memberships');
});

it('rejects an invalid workspace header on me endpoint', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();

    Sanctum::actingAs($workspaceA['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspaceB['freelancer']->id)
        ->getJson($this->apiUrl('me'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('respects a switched workspace header on subsequent tenant requests', function () {
    $user = User::factory()->freelancer()->create();
    $first = Freelancer::factory()->active()->create(['owner_user_id' => $user->id]);
    $second = Freelancer::factory()->active()->create();

    FreelancerMembership::factory()->for($first)->for($user)->owner()->create();
    FreelancerMembership::factory()->for($second)->for($user)->member()->create();

    Sanctum::actingAs($user);

    $this->withHeader('X-Freelancer-Id', (string) $first->id)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('active_freelancer.id', $first->id);

    $this->withHeader('X-Freelancer-Id', (string) $second->id)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('active_freelancer.id', $second->id);

    $this->withHeader('X-Freelancer-Id', (string) $second->id)
        ->getJson($this->apiUrl('clients'))
        ->assertOk();
});

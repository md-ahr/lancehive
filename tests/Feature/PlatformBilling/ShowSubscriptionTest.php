<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('returns subscription details for workspace owner', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 3]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('subscription'))
        ->assertOk()
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('plan.name', $plan->name)
        ->assertJsonPath('usage.max_clients', 3);
});

it('denies non owners from viewing subscription', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    Subscription::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('subscription'))
        ->assertForbidden();
});

<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

it('returns checkout url for self serve plan', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['stripe_price_monthly_id' => 'price_test']);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/checkout'), [
            'plan_id' => $plan->id,
            'billing_interval' => 'monthly',
        ])
        ->assertOk()
        ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/test-session');
});

it('rejects custom plan checkout', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->custom()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/checkout'), [
            'plan_id' => $plan->id,
            'billing_interval' => 'monthly',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plan_id']);
});

it('allows checkout when workspace is read only', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['stripe_price_monthly_id' => 'price_test']);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->readOnly()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/checkout'), [
            'plan_id' => $plan->id,
            'billing_interval' => 'monthly',
        ])
        ->assertOk();
});

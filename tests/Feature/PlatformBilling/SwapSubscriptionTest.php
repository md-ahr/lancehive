<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

it('swaps plan and billing interval', function () {
    $workspace = $this->createTenantWorkspace();
    $currentPlan = Plan::factory()->create(['stripe_price_monthly_id' => 'price_old']);
    $newPlan = Plan::factory()->create(['stripe_price_yearly_id' => 'price_new']);
    Subscription::factory()->for($workspace['freelancer'])->for($currentPlan)->active()->create([
        'provider_subscription_id' => 'sub_swap_test',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/swap'), [
            'plan_id' => $newPlan->id,
            'billing_interval' => BillingInterval::Yearly->value,
        ])
        ->assertOk()
        ->assertJsonPath('plan_id', $newPlan->id)
        ->assertJsonPath('billing_interval', 'yearly');
});

it('rejects custom plan swap', function () {
    $workspace = $this->createTenantWorkspace();
    $currentPlan = Plan::factory()->create(['stripe_price_monthly_id' => 'price_old']);
    $customPlan = Plan::factory()->custom()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($currentPlan)->active()->create([
        'provider_subscription_id' => 'sub_custom_swap',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/swap'), [
            'plan_id' => $customPlan->id,
            'billing_interval' => BillingInterval::Yearly->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plan_id']);
});

it('allows swap when workspace is read only', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['stripe_price_yearly_id' => 'price_yearly']);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->readOnly()->create([
        'provider_subscription_id' => 'sub_readonly_swap',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/swap'), [
            'plan_id' => $plan->id,
            'billing_interval' => BillingInterval::Yearly->value,
        ])
        ->assertOk();
});

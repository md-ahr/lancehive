<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;

it('syncs stripe price ids for active self serve plans', function () {
    $plan = Plan::factory()->create([
        'is_custom' => false,
        'is_active' => true,
        'stripe_price_monthly_id' => null,
        'stripe_price_yearly_id' => null,
    ]);

    $this->artisan('plans:sync-stripe', ['--plan' => $plan->id])
        ->assertSuccessful();

    $plan->refresh();

    expect($plan->stripe_price_monthly_id)->not->toBeNull()
        ->and($plan->stripe_price_yearly_id)->not->toBeNull();
});

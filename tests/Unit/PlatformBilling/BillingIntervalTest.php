<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Models\Subscription;

it('exposes all billing interval values', function () {
    expect(BillingInterval::values())->toBe(['monthly', 'yearly']);
});

it('round trips billing interval through the model cast', function () {
    $subscription = Subscription::factory()->create(['billing_interval' => BillingInterval::Yearly]);

    expect($subscription->billing_interval)->toBe(BillingInterval::Yearly)
        ->and($subscription->getAttributes()['billing_interval'])->toBe('yearly');
});

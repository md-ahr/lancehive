<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Http\Resources\SubscriptionSummaryResource;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

it('serializes subscription summary resource with expected keys', function () {
    $plan = Plan::factory()->create(['name' => 'Starter']);
    $subscription = Subscription::factory()->for($plan)->create();
    $subscription->load('plan');

    $payload = (new SubscriptionSummaryResource($subscription))->resolve();

    expect($payload)->toHaveKeys([
        'status',
        'plan_name',
        'read_only',
        'trial_ends_at',
    ])
        ->and($payload['status'])->toBe('trialing')
        ->and($payload['plan_name'])->toBe('Starter')
        ->and($payload['read_only'])->toBeFalse();
});

it('marks read only subscriptions correctly', function () {
    $subscription = Subscription::factory()->readOnly()->create();
    $subscription->load('plan');

    $payload = (new SubscriptionSummaryResource($subscription))->resolve();

    expect($payload['read_only'])->toBeTrue()
        ->and($payload['status'])->toBe('read_only');
});

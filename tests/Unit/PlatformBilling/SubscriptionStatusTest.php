<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Subscription;

dataset('writable subscription statuses', [
    'trialing' => [SubscriptionStatus::Trialing, true],
    'active' => [SubscriptionStatus::Active, true],
    'past due' => [SubscriptionStatus::PastDue, true],
    'read only' => [SubscriptionStatus::ReadOnly, false],
    'canceled' => [SubscriptionStatus::Canceled, false],
]);

it('exposes all subscription status values', function () {
    expect(SubscriptionStatus::values())->toBe([
        'trialing', 'active', 'past_due', 'read_only', 'canceled',
    ]);
});

it('round trips subscription status through the model cast', function () {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::PastDue]);

    expect($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->getAttributes()['status'])->toBe('past_due');
});

it('reports whether subscription status allows writes', function (SubscriptionStatus $status, bool $canWrite) {
    expect($status->canWrite())->toBe($canWrite);
})->with('writable subscription statuses');

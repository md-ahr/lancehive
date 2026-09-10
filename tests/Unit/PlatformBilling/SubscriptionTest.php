<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

it('relates to freelancer plan and charges', function () {
    $subscription = Subscription::factory()->create();

    expect($subscription->freelancer)->not->toBeNull()
        ->and($subscription->plan)->not->toBeNull()
        ->and($subscription->charges())->toBeInstanceOf(HasMany::class);
});

it('exposes subscription helper methods', function () {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Trialing]);

    expect($subscription->onTrial())->toBeTrue()
        ->and($subscription->isActive())->toBeFalse()
        ->and($subscription->isPastDue())->toBeFalse()
        ->and($subscription->isReadOnly())->toBeFalse()
        ->and($subscription->canWrite())->toBeTrue();
});

it('enforces one subscription per freelancer', function () {
    $subscription = Subscription::factory()->create();

    expect(fn () => Subscription::factory()->create([
        'freelancer_id' => $subscription->freelancer_id,
    ]))->toThrow(QueryException::class);
});

it('returns calendar days remaining until trial end', function () {
    $this->travelTo(now()->startOfDay());

    $subscription = Subscription::factory()->make([
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->addDays(3)->endOfDay(),
    ]);

    expect($subscription->daysRemaining())->toBe(3);
});

it('returns zero days remaining after trial end', function () {
    $this->travelTo(now()->startOfDay());

    $subscription = Subscription::factory()->make([
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->subDay()->endOfDay(),
    ]);

    expect($subscription->daysRemaining())->toBe(0);
});

it('returns calendar days remaining until billing period end', function () {
    $this->travelTo(now()->startOfDay());

    $subscription = Subscription::factory()->make([
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addDays(5)->endOfDay(),
    ]);

    expect($subscription->daysRemaining())->toBe(5);
});

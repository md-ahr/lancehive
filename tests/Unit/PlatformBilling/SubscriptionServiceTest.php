<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use App\Features\PlatformBilling\Services\SubscriptionService;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Facades\Cache;

it('starts a trial subscription', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();

    $subscription = app(SubscriptionService::class)->startTrial($freelancer, $plan, 14);

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->trial_ends_at?->isFuture())->toBeTrue();
});

it('creates a checkout session url', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create([
        'stripe_price_monthly_id' => 'price_monthly_test',
    ]);

    $url = app(SubscriptionService::class)->createCheckoutSession(
        $freelancer,
        $plan,
        BillingInterval::Monthly,
    );

    expect($url)->toBe('https://checkout.stripe.com/test-session');
});

it('assigns a custom plan manually', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->custom()->create();

    $subscription = app(SubscriptionService::class)->assignCustomPlan($freelancer, $plan);

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->provider)->toBe(SubscriptionProvider::Manual);
});

it('syncs invoice paid webhook and creates charge', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_test_123']);

    Cache::flush();

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_test_123',
                'subscription' => 'sub_test_123',
                'amount_paid' => 20000,
                'currency' => 'bdt',
                'period_start' => now()->timestamp,
                'period_end' => now()->addMonth()->timestamp,
            ],
        ],
    ]);

    expect(SubscriptionCharge::query()->where('provider_charge_id', 'in_test_123')->exists())->toBeTrue()
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and(Cache::has('subscription:freelancer:'.$freelancer->id))->toBeFalse();
});

it('ignores checkout completed webhook with invalid billing interval', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $newPlan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->create([
            'billing_interval' => BillingInterval::Monthly,
            'provider_subscription_id' => null,
        ]);

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'subscription' => 'sub_test_invalid_interval',
                'metadata' => [
                    'freelancer_id' => (string) $freelancer->id,
                    'plan_id' => (string) $newPlan->id,
                    'billing_interval' => 'invalid',
                ],
            ],
        ],
    ]);

    $subscription->refresh();

    expect($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->billing_interval)->toBe(BillingInterval::Monthly)
        ->and($subscription->provider_subscription_id)->toBeNull();
});

it('syncs checkout completed webhook and activates subscription', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $newPlan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->create([
            'billing_interval' => null,
            'provider_subscription_id' => null,
        ]);

    Cache::flush();

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'subscription' => 'sub_test_checkout',
                'metadata' => [
                    'freelancer_id' => (string) $freelancer->id,
                    'plan_id' => (string) $newPlan->id,
                    'billing_interval' => BillingInterval::Yearly->value,
                ],
            ],
        ],
    ]);

    $subscription->refresh();

    expect($subscription->plan_id)->toBe($newPlan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->billing_interval)->toBe(BillingInterval::Yearly)
        ->and($subscription->provider)->toBe(SubscriptionProvider::Stripe)
        ->and($subscription->provider_subscription_id)->toBe('sub_test_checkout')
        ->and(Cache::has('subscription:freelancer:'.$freelancer->id))->toBeFalse();
});

it('syncs payment failed webhook to past due and creates failed charge', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_fail_unit']);

    Cache::flush();

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_fail_unit',
                'subscription' => 'sub_fail_unit',
                'amount_due' => 20000,
                'currency' => 'bdt',
                'attempt_count' => 1,
            ],
        ],
    ]);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and(SubscriptionCharge::query()->where('provider_charge_id', 'in_fail_unit')->exists())->toBeTrue()
        ->and(Cache::has('subscription:freelancer:'.$freelancer->id))->toBeFalse();
});

it('syncs subscription updated webhook', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_update_unit']);

    Cache::flush();

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_update_unit',
                'status' => 'past_due',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
            ],
        ],
    ]);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and(Cache::has('subscription:freelancer:'.$freelancer->id))->toBeFalse();
});

it('syncs subscription deleted webhook to canceled', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_delete_unit']);

    Cache::flush();

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_delete_unit',
            ],
        ],
    ]);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Canceled)
        ->and($subscription->fresh()->canceled_at)->not->toBeNull()
        ->and(Cache::has('subscription:freelancer:'.$freelancer->id))->toBeFalse();
});

it('ignores duplicate webhook charge ids', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($freelancer)
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_test_dup']);

    SubscriptionCharge::factory()->for($subscription)->create([
        'provider_charge_id' => 'in_test_dup',
    ]);

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_test_dup',
                'subscription' => 'sub_test_dup',
                'amount_paid' => 20000,
                'currency' => 'bdt',
            ],
        ],
    ]);

    expect(SubscriptionCharge::query()->where('provider_charge_id', 'in_test_dup')->count())->toBe(1);
});

<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Notifications\PaymentFailedNotification;
use App\Features\PlatformBilling\Notifications\RenewalReceiptNotification;
use App\Features\PlatformBilling\Notifications\SubscriptionCanceledNotification;
use App\Features\PlatformBilling\Notifications\TrialEndingSoonNotification;
use App\Features\PlatformBilling\Services\SubscriptionService;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Facades\Notification;

it('sends trial ending notifications only once per trial', function () {
    Notification::fake();

    $owner = User::factory()->user()->create();
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $owner->id]);
    $plan = Plan::factory()->create();
    Subscription::factory()->for($freelancer)->for($plan)->create([
        'trial_ends_at' => now()->addDays(3),
    ]);

    $this->artisan('subscriptions:notify-trial-ending')->assertSuccessful();
    $this->artisan('subscriptions:notify-trial-ending')->assertSuccessful();

    Notification::assertSentToTimes($owner, TrialEndingSoonNotification::class, 1);
});

it('sends trial ending notifications', function () {
    Notification::fake();

    $owner = User::factory()->user()->create();
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $owner->id]);
    $plan = Plan::factory()->create();
    Subscription::factory()->for($freelancer)->for($plan)->create([
        'trial_ends_at' => now()->addDays(3),
    ]);

    $this->artisan('subscriptions:notify-trial-ending')->assertSuccessful();

    Notification::assertSentTo($owner, TrialEndingSoonNotification::class);
});

it('sends payment failed notification from webhook', function () {
    Notification::fake();

    $owner = User::factory()->user()->create();
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $owner->id]);
    $plan = Plan::factory()->create();
    Subscription::factory()->for($freelancer)->for($plan)->active()->create([
        'provider_subscription_id' => 'sub_notify_fail',
    ]);

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_notify_fail',
                'subscription' => 'sub_notify_fail',
                'amount_due' => 20000,
                'currency' => 'bdt',
                'attempt_count' => 1,
            ],
        ],
    ]);

    Notification::assertSentTo($owner, PaymentFailedNotification::class);
});

it('sends renewal receipt notification from webhook', function () {
    Notification::fake();

    $owner = User::factory()->user()->create();
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $owner->id]);
    $plan = Plan::factory()->create();
    Subscription::factory()->for($freelancer)->for($plan)->active()->create([
        'provider_subscription_id' => 'sub_notify_paid',
    ]);

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_notify_paid',
                'subscription' => 'sub_notify_paid',
                'amount_paid' => 20000,
                'currency' => 'bdt',
            ],
        ],
    ]);

    Notification::assertSentTo($owner, RenewalReceiptNotification::class);
});

it('sends canceled notification from webhook', function () {
    Notification::fake();

    $owner = User::factory()->user()->create();
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $owner->id]);
    $plan = Plan::factory()->create();
    Subscription::factory()->for($freelancer)->for($plan)->active()->create([
        'provider_subscription_id' => 'sub_notify_cancel',
    ]);

    app(SubscriptionService::class)->syncFromStripeWebhook([
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_notify_cancel',
            ],
        ],
    ]);

    Notification::assertSentTo($owner, SubscriptionCanceledNotification::class);
});

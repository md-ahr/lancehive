<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

it('allows writes during valid trial', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->create([
        'trial_ends_at' => now()->addDays(10),
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Matrix Trial Client'])
        ->assertCreated();
});

it('blocks fourth client on starter plan', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 3]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    Client::factory()->count(3)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Fourth Client'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'plan_limit_exceeded');
});

it('blocks sixth project on starter plan', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $plan = Plan::factory()->create(['max_projects' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    Project::factory()->count(5)->for($workspace['freelancer'])->for($client)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/projects"), [
            'name' => 'Sixth Project',
            'hourly_rate' => '1200.00',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'plan_limit_exceeded');
});

it('allows unlimited clients on custom plan', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->custom()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    Client::factory()->count(5)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Another Client'])
        ->assertCreated();
});

it('rejects webhook with invalid signature', function () {
    $this->postJson($this->apiUrl('webhooks/stripe'), [
        'type' => 'invoice.paid',
        'data' => ['object' => ['id' => 'in_invalid_sig']],
    ])->assertForbidden();
});

it('handles checkout completed webhook', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    $newPlan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($workspace['freelancer'])
        ->for($plan)
        ->create(['provider_subscription_id' => null]);

    Cache::flush();

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson($this->apiUrl('webhooks/stripe'), [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_checkout_feature',
                    'metadata' => [
                        'freelancer_id' => (string) $workspace['freelancer']->id,
                        'plan_id' => (string) $newPlan->id,
                        'billing_interval' => 'monthly',
                    ],
                ],
            ],
        ])
        ->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->fresh()->plan_id)->toBe($newPlan->id)
        ->and($subscription->fresh()->provider_subscription_id)->toBe('sub_checkout_feature');
});

it('handles invoice paid webhook', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($workspace['freelancer'])
        ->for($plan)
        ->readOnly()
        ->create(['provider_subscription_id' => 'sub_matrix_test']);

    Cache::flush();

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson($this->apiUrl('webhooks/stripe'), [
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_matrix_test',
                    'subscription' => 'sub_matrix_test',
                    'amount_paid' => 20000,
                    'currency' => 'bdt',
                    'period_start' => now()->timestamp,
                    'period_end' => now()->addMonth()->timestamp,
                ],
            ],
        ])
        ->assertOk();

    expect($subscription->fresh()->status->value)->toBe('active')
        ->and(SubscriptionCharge::query()->where('provider_charge_id', 'in_matrix_test')->exists())->toBeTrue()
        ->and(app(SubscriptionCache::class)->forFreelancer($workspace['freelancer']->id)?->status->value)->toBe('active');
});

it('handles invoice payment failed webhook', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($workspace['freelancer'])
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_payment_failed']);

    Cache::flush();

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson($this->apiUrl('webhooks/stripe'), [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_payment_failed',
                    'subscription' => 'sub_payment_failed',
                    'amount_due' => 20000,
                    'currency' => 'bdt',
                    'attempt_count' => 1,
                ],
            ],
        ])
        ->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and(SubscriptionCharge::query()->where('provider_charge_id', 'in_payment_failed')->exists())->toBeTrue()
        ->and(SubscriptionCharge::query()->where('provider_charge_id', 'in_payment_failed')->value('status'))->toBe(SubscriptionChargeStatus::Failed)
        ->and(Cache::has('subscription:freelancer:'.$workspace['freelancer']->id))->toBeFalse();
});

it('handles subscription updated webhook', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($workspace['freelancer'])
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_updated']);

    Cache::flush();

    $periodEnd = now()->addMonth()->timestamp;

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson($this->apiUrl('webhooks/stripe'), [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_updated',
                    'status' => 'past_due',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => $periodEnd,
                    'cancel_at_period_end' => false,
                ],
            ],
        ])
        ->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and(Cache::has('subscription:freelancer:'.$workspace['freelancer']->id))->toBeFalse();
});

it('handles subscription deleted webhook', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->for($workspace['freelancer'])
        ->for($plan)
        ->active()
        ->create(['provider_subscription_id' => 'sub_deleted']);

    Cache::flush();

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson($this->apiUrl('webhooks/stripe'), [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_deleted',
                ],
            ],
        ])
        ->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Canceled)
        ->and($subscription->fresh()->canceled_at)->not->toBeNull()
        ->and(Cache::has('subscription:freelancer:'.$workspace['freelancer']->id))->toBeFalse();
});

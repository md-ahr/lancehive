<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

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

it('allows unlimited clients on custom plan', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->custom()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    Client::factory()->count(5)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Another Client'])
        ->assertCreated();
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

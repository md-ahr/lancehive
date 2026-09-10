<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Notifications\SubscriptionCanceledNotification;
use Illuminate\Support\Facades\Notification;

it('cancels subscription at period end', function () {
    Notification::fake();

    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create([
        'provider_subscription_id' => 'sub_cancel_test',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/cancel'))
        ->assertOk()
        ->assertJsonPath('canceled_at', fn ($value) => $value !== null);

    Notification::assertSentTo($workspace['user'], SubscriptionCanceledNotification::class);
});

it('allows cancel when workspace is read only', function () {
    Notification::fake();

    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->readOnly()->create([
        'provider_subscription_id' => 'sub_readonly_cancel',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('subscription/cancel'))
        ->assertOk();
});

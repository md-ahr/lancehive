<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Features\PlatformBilling\Contracts\StripeGateway;
use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\Tenancy\Models\Freelancer;

final class FakeStripeGateway implements StripeGateway
{
    public string $checkoutUrl = 'https://checkout.stripe.com/test-session';

    /**
     * @param  array<string, string|int>  $metadata
     */
    public function createCheckoutSession(
        Freelancer $freelancer,
        Plan $plan,
        BillingInterval $interval,
        array $metadata = [],
    ): string {
        return $this->checkoutUrl;
    }

    public function swapSubscription(
        Freelancer $freelancer,
        string $providerSubscriptionId,
        Plan $plan,
        BillingInterval $interval,
    ): void {}

    public function cancelSubscriptionAtPeriodEnd(
        Freelancer $freelancer,
        string $providerSubscriptionId,
    ): void {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function syncPlanPrices(Plan $plan, array $options = []): Plan
    {
        $plan->stripe_price_monthly_id = $plan->stripe_price_monthly_id ?? 'price_monthly_test';
        $plan->stripe_price_yearly_id = $plan->stripe_price_yearly_id ?? 'price_yearly_test';
        $plan->save();

        return $plan->fresh();
    }
}

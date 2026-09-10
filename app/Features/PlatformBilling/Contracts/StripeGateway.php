<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Contracts;

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\Tenancy\Models\Freelancer;

interface StripeGateway
{
    /**
     * @param  array<string, string|int>  $metadata
     */
    public function createCheckoutSession(
        Freelancer $freelancer,
        Plan $plan,
        BillingInterval $interval,
        array $metadata = [],
    ): string;

    public function swapSubscription(
        Freelancer $freelancer,
        string $providerSubscriptionId,
        Plan $plan,
        BillingInterval $interval,
    ): void;

    public function cancelSubscriptionAtPeriodEnd(
        Freelancer $freelancer,
        string $providerSubscriptionId,
    ): void;

    /**
     * @param  array<string, mixed>  $options
     */
    public function syncPlanPrices(Plan $plan, array $options = []): Plan;
}

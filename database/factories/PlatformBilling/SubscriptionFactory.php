<?php

declare(strict_types=1);

namespace Database\Factories\PlatformBilling;

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => Freelancer::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Trialing,
            'billing_interval' => null,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => null,
            'current_period_end' => null,
            'read_only_at' => null,
            'canceled_at' => null,
            'provider' => SubscriptionProvider::Manual,
            'provider_subscription_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Active,
            'billing_interval' => BillingInterval::Monthly,
            'trial_ends_at' => null,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function readOnly(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::ReadOnly,
            'read_only_at' => now(),
        ]);
    }

    public function expiredTrial(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->subDay(),
        ]);
    }
}

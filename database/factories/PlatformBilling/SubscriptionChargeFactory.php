<?php

declare(strict_types=1);

namespace Database\Factories\PlatformBilling;

use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionCharge>
 */
class SubscriptionChargeFactory extends Factory
{
    protected $model = SubscriptionCharge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'amount' => fake()->randomFloat(2, 200, 2000),
            'currency' => 'BDT',
            'status' => SubscriptionChargeStatus::Pending,
            'paid_at' => null,
            'provider_charge_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionChargeStatus::Paid,
            'paid_at' => now(),
            'provider_charge_id' => 'ch_'.fake()->uuid(),
        ]);
    }
}

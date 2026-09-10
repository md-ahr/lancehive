<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class SubscriptionChargeSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        $subscription = Subscription::query()->where('freelancer_id', $freelancer->id)->firstOrFail();

        SubscriptionCharge::query()->updateOrCreate(
            ['provider_charge_id' => 'demo_charge_paid_001'],
            [
                'subscription_id' => $subscription->id,
                'amount' => 200,
                'currency' => 'BDT',
                'status' => SubscriptionChargeStatus::Paid,
                'paid_at' => now()->subMonth(),
            ],
        );

        SubscriptionCharge::query()->updateOrCreate(
            ['provider_charge_id' => 'demo_charge_pending_001'],
            [
                'subscription_id' => $subscription->id,
                'amount' => 200,
                'currency' => 'BDT',
                'status' => SubscriptionChargeStatus::Pending,
                'paid_at' => null,
            ],
        );
    }
}

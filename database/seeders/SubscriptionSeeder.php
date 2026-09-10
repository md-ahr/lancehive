<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        $starterPlan = Plan::query()->where('slug', DemoData::PLAN_STARTER_SLUG)->firstOrFail();

        Subscription::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id],
            [
                'plan_id' => $starterPlan->id,
                'status' => SubscriptionStatus::Trialing,
                'billing_interval' => null,
                'trial_ends_at' => now()->addDays(14),
                'current_period_start' => null,
                'current_period_end' => null,
                'read_only_at' => null,
                'canceled_at' => null,
                'provider' => SubscriptionProvider::Manual,
                'provider_subscription_id' => null,
            ],
        );
    }
}

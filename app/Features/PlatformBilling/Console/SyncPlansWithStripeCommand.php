<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Console;

use App\Features\PlatformBilling\Cache\PlanCache;
use App\Features\PlatformBilling\Contracts\StripeGateway;
use App\Features\PlatformBilling\Models\Plan;
use Illuminate\Console\Command;

final class SyncPlansWithStripeCommand extends Command
{
    protected $signature = 'plans:sync-stripe {--plan= : Sync a single plan by ID}';

    protected $description = 'Create Stripe products and prices for self-serve plans';

    public function handle(StripeGateway $stripeGateway, PlanCache $planCache): int
    {
        $planId = $this->option('plan');

        $plans = Plan::query()
            ->when($planId !== null, fn ($query) => $query->whereKey($planId))
            ->where('is_custom', false)
            ->where('is_active', true)
            ->get();

        if ($plans->isEmpty()) {
            $this->warn('No plans to sync.');

            return self::SUCCESS;
        }

        foreach ($plans as $plan) {
            $stripeGateway->syncPlanPrices($plan);
            $this->info("Synced plan: {$plan->name} ({$plan->id})");
        }

        $planCache->forget();

        return self::SUCCESS;
    }
}

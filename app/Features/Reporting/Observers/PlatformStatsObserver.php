<?php

declare(strict_types=1);

namespace App\Features\Reporting\Observers;

use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Reporting\Services\InvalidateCachedSnapshots;
use App\Features\Tenancy\Models\Freelancer;

final class PlatformStatsObserver
{
    public function __construct(private readonly InvalidateCachedSnapshots $invalidator) {}

    public function savedFreelancer(Freelancer $freelancer): void
    {
        if ($freelancer->wasRecentlyCreated || $freelancer->wasChanged(['status'])) {
            $this->invalidator->platform();
        }
    }

    public function deletedFreelancer(Freelancer $freelancer): void
    {
        $this->invalidator->platform();
    }

    public function savedSubscription(Subscription $subscription): void
    {
        $this->invalidator->platform();
    }

    public function deletedSubscription(Subscription $subscription): void
    {
        $this->invalidator->platform();
    }

    public function savedPlan(Plan $plan): void
    {
        $this->invalidator->platform();
    }
}

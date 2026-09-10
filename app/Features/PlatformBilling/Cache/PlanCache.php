<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Cache;

use App\Features\PlatformBilling\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class PlanCache
{
    private const string KEY = 'plans:active';

    private const int TTL_SECONDS = 3600;

    /**
     * @return Collection<int, Plan>
     */
    public function activePlans(): Collection
    {
        /** @var Collection<int, Plan> $plans */
        $plans = Cache::remember(self::KEY, self::TTL_SECONDS, fn (): Collection => Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get());

        return $plans;
    }

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}

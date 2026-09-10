<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Cache;

use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Support\Facades\Cache;

final class SubscriptionCache
{
    private const int TTL_SECONDS = 300;

    public function forFreelancer(int $freelancerId): ?Subscription
    {
        return Cache::remember(
            $this->key($freelancerId),
            self::TTL_SECONDS,
            fn (): ?Subscription => Subscription::query()
                ->with('plan')
                ->where('freelancer_id', $freelancerId)
                ->first(),
        );
    }

    public function forget(int $freelancerId): void
    {
        Cache::forget($this->key($freelancerId));
    }

    private function key(int $freelancerId): string
    {
        return "subscription:freelancer:{$freelancerId}";
    }
}

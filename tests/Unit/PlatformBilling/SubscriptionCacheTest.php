<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Cache\SubscriptionCache;
use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Support\Facades\Cache;

it('caches subscriptions per freelancer and can forget them', function () {
    Cache::flush();

    $subscription = Subscription::factory()->active()->create();
    $cache = app(SubscriptionCache::class);

    expect($cache->forFreelancer($subscription->freelancer_id)?->id)->toBe($subscription->id)
        ->and(Cache::has("subscription:freelancer:{$subscription->freelancer_id}"))->toBeTrue();

    $cache->forget($subscription->freelancer_id);

    expect(Cache::has("subscription:freelancer:{$subscription->freelancer_id}"))->toBeFalse();
});

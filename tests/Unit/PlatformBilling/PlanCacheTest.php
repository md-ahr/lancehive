<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Cache\PlanCache;
use App\Features\PlatformBilling\Models\Plan;
use Illuminate\Support\Facades\Cache;

it('caches active plans and forgets the cache key', function () {
    Cache::flush();

    Plan::factory()->inactive()->create();
    $active = Plan::factory()->create(['sort_order' => 1]);

    $cache = app(PlanCache::class);
    $plans = $cache->activePlans();

    expect($plans)->toHaveCount(1)
        ->and($plans->first()->id)->toBe($active->id)
        ->and(Cache::has('plans:active'))->toBeTrue();

    $cache->forget();

    expect(Cache::has('plans:active'))->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Features\Reporting\Cache\PlatformStatsCache;
use App\Features\Reporting\Cache\WorkspaceStatsCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('remembers workspace stats in cache', function () {
    $cache = app(WorkspaceStatsCache::class);

    $stats = $cache->remember(42, fn (): array => ['active_clients' => 3]);

    expect($stats)->toBe(['active_clients' => 3])
        ->and(Cache::has('workspace_stats:freelancer:42'))->toBeTrue();
});

it('forgets workspace stats cache key', function () {
    $cache = app(WorkspaceStatsCache::class);

    $cache->remember(42, fn (): array => ['active_clients' => 3]);
    $cache->forget(42);

    expect(Cache::has('workspace_stats:freelancer:42'))->toBeFalse();
});

it('remembers platform stats in cache', function () {
    $cache = app(PlatformStatsCache::class);

    $stats = $cache->remember(fn (): array => ['trials_ending_soon' => 2]);

    expect($stats)->toBe(['trials_ending_soon' => 2])
        ->and(Cache::has('platform_stats:snapshot'))->toBeTrue();
});

it('forgets platform stats cache key', function () {
    $cache = app(PlatformStatsCache::class);

    $cache->remember(fn (): array => ['trials_ending_soon' => 2]);
    $cache->forget();

    expect(Cache::has('platform_stats:snapshot'))->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Features\Settings\Cache\PlatformSettingsCache;
use App\Features\Settings\Models\PlatformSettings;
use Illuminate\Support\Facades\Cache;

it('caches platform settings and forgets the cache key', function () {
    Cache::flush();

    PlatformSettings::factory()->create(['id' => 1]);

    $cache = app(PlatformSettingsCache::class);
    $settings = $cache->get();

    expect($settings)->not->toBeNull()
        ->and($settings?->id)->toBe(1)
        ->and(Cache::has('platform_settings:singleton'))->toBeTrue();

    $cache->forget();

    expect(Cache::has('platform_settings:singleton'))->toBeFalse();
});

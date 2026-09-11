<?php

declare(strict_types=1);

use App\Features\Settings\Cache\WorkspaceSettingsCache;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('remembers workspace settings in cache', function () {
    $freelancer = Freelancer::factory()->create([
        'default_currency' => 'USD',
        'business_name' => 'Cached Studio',
    ]);

    $cache = app(WorkspaceSettingsCache::class);
    $settings = $cache->get($freelancer->id);

    expect($settings)
        ->not->toBeNull()
        ->and($settings['default_currency'])->toBe('USD')
        ->and($settings['business_name'])->toBe('Cached Studio')
        ->and(Cache::has("workspace_settings:freelancer:{$freelancer->id}"))->toBeTrue();
});

it('forgets workspace settings cache key', function () {
    $freelancer = Freelancer::factory()->create();
    $cache = app(WorkspaceSettingsCache::class);

    $cache->get($freelancer->id);
    $cache->forget($freelancer->id);

    expect(Cache::has("workspace_settings:freelancer:{$freelancer->id}"))->toBeFalse();
});

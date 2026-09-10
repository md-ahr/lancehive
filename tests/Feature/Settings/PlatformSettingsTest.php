<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\Settings\Cache\PlatformSettingsCache;
use App\Features\Settings\Models\PlatformSettings;
use Database\Seeders\Support\DemoData;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    PlatformSettings::factory()->create(['id' => 1]);
});

it('allows super admin to read platform settings', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->getJson($this->apiUrl('admin/settings'))
        ->assertOk()
        ->assertJsonPath('default_trial_days', 14)
        ->assertJsonPath('default_plan_slug', DemoData::PLAN_STARTER_SLUG);
});

it('denies platform settings for regular users', function () {
    Sanctum::actingAs(User::factory()->user()->create());

    $this->getJson($this->apiUrl('admin/settings'))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

it('allows super admin to patch platform settings and invalidates cache', function () {
    Cache::flush();
    Sanctum::actingAs(User::factory()->superAdmin()->create());
    $plan = Plan::factory()->create(['slug' => 'enterprise']);

    app(PlatformSettingsCache::class)->get();
    expect(Cache::has('platform_settings:singleton'))->toBeTrue();

    $this->patchJson($this->apiUrl('admin/settings'), [
        'default_trial_days' => 30,
        'default_plan_slug' => 'enterprise',
    ])
        ->assertOk()
        ->assertJsonPath('default_trial_days', 30)
        ->assertJsonPath('default_plan_slug', 'enterprise');

    expect(Cache::has('platform_settings:singleton'))->toBeFalse();
});

it('returns validation error for unknown plan slug', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->patchJson($this->apiUrl('admin/settings'), [
        'default_plan_slug' => 'missing-plan',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['default_plan_slug']);
});

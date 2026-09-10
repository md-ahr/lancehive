<?php

declare(strict_types=1);

use App\Features\Settings\Models\PlatformSettings;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\Support\DemoData;

it('creates singleton platform settings with defaults', function () {
    $this->seed(PlatformSettingsSeeder::class);

    $settings = PlatformSettings::query()->find(1);

    expect($settings)->not->toBeNull()
        ->and($settings->default_trial_days)->toBe(14)
        ->and($settings->default_plan_slug)->toBe(DemoData::PLAN_STARTER_SLUG)
        ->and($settings->maintenance_mode)->toBeFalse();
});

it('is idempotent when re-run', function () {
    $this->seed(PlatformSettingsSeeder::class);
    $this->seed(PlatformSettingsSeeder::class);

    expect(PlatformSettings::query()->count())->toBe(1);
});

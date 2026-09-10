<?php

declare(strict_types=1);

namespace App\Features\Settings\Services;

use App\Features\Settings\Cache\PlatformSettingsCache;
use App\Features\Settings\Models\PlatformSettings;
use Database\Seeders\Support\DemoData;

final class PlatformSettingsService
{
    public function __construct(private readonly PlatformSettingsCache $cache) {}

    public function get(): PlatformSettings
    {
        $settings = $this->cache->get();

        if ($settings instanceof PlatformSettings) {
            return $settings;
        }

        $settings = PlatformSettings::query()->firstOrCreate(
            ['id' => 1],
            [
                'default_trial_days' => 14,
                'default_plan_slug' => DemoData::PLAN_STARTER_SLUG,
                'support_email' => (string) config('mail.from.address'),
                'maintenance_mode' => false,
            ],
        );

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): PlatformSettings
    {
        $settings = $this->get();
        $settings->update($data);
        $this->forgetCache();

        return $settings->fresh();
    }

    public function defaultTrialDays(): int
    {
        return $this->get()->default_trial_days;
    }

    public function forgetCache(): void
    {
        $this->cache->forget();
    }
}

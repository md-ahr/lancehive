<?php

declare(strict_types=1);

namespace App\Features\Settings\Cache;

use App\Features\Settings\Models\PlatformSettings;
use Illuminate\Support\Facades\Cache;

final class PlatformSettingsCache
{
    private const string KEY = 'platform_settings:singleton';

    private const int TTL_SECONDS = 300;

    public function get(): ?PlatformSettings
    {
        /** @var PlatformSettings|null $settings */
        $settings = Cache::remember(self::KEY, self::TTL_SECONDS, function (): ?PlatformSettings {
            return PlatformSettings::query()->find(1);
        });

        return $settings;
    }

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}

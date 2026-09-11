<?php

declare(strict_types=1);

namespace App\Features\Reporting\Cache;

use Illuminate\Support\Facades\Cache;

final class PlatformStatsCache
{
    private const string KEY = 'platform_stats:snapshot';

    private const int TTL_SECONDS = 60;

    /**
     * @param  callable(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(callable $resolver): array
    {
        /** @var array<string, mixed> $stats */
        $stats = Cache::remember(self::KEY, self::TTL_SECONDS, $resolver);

        return $stats;
    }

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Reporting\Cache;

use Illuminate\Support\Facades\Cache;

final class WorkspaceStatsCache
{
    private const int TTL_SECONDS = 60;

    /**
     * @param  callable(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(int $freelancerId, callable $resolver): array
    {
        /** @var array<string, mixed> $stats */
        $stats = Cache::remember($this->key($freelancerId), self::TTL_SECONDS, $resolver);

        return $stats;
    }

    public function forget(int $freelancerId): void
    {
        Cache::forget($this->key($freelancerId));
    }

    private function key(int $freelancerId): string
    {
        return "workspace_stats:freelancer:{$freelancerId}";
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Cache\PlatformStatsCache;
use App\Features\Reporting\Cache\WorkspaceStatsCache;

final class InvalidateCachedSnapshots
{
    public function __construct(
        private readonly WorkspaceStatsCache $workspaceStatsCache,
        private readonly PlatformStatsCache $platformStatsCache,
    ) {}

    public function workspace(int $freelancerId): void
    {
        $this->workspaceStatsCache->forget($freelancerId);
    }

    public function platform(): void
    {
        $this->platformStatsCache->forget();
    }
}

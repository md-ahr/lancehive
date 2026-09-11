<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Reporting\Cache\WorkspaceStatsCache;
use App\Features\Reporting\Services\ReportQueryService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('invalidates workspace stats cache when a client is created', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);
    $cache = app(WorkspaceStatsCache::class);

    app(ReportQueryService::class)->workspaceOverview();

    expect(Cache::has('workspace_stats:freelancer:'.$workspace['freelancer']->id))->toBeTrue();

    Client::query()->create([
        'freelancer_id' => $workspace['freelancer']->id,
        'name' => 'New Client',
        'status' => ClientStatus::Active,
    ]);

    expect(Cache::has('workspace_stats:freelancer:'.$workspace['freelancer']->id))->toBeFalse();
});

it('invalidates platform stats cache when a freelancer is created', function () {
    Cache::flush();
    app(ReportQueryService::class)->platformOverview();

    expect(Cache::has('platform_stats:snapshot'))->toBeTrue();

    $this->createTenantWorkspace();

    expect(Cache::has('platform_stats:snapshot'))->toBeFalse();
});

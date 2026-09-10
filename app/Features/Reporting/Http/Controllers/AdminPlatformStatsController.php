<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Features\Reporting\Http\Requests\ShowPlatformStatsRequest;
use App\Features\Reporting\Http\Resources\PlatformStatsResource;
use App\Features\Reporting\Services\ReportQueryService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Reports', weight: 45)]
final class AdminPlatformStatsController extends Controller
{
    public function __construct(private readonly ReportQueryService $reportQueryService) {}

    #[Endpoint(title: 'Platform dashboard stats', description: 'Platform dashboard snapshot for super-admins.')]
    public function show(ShowPlatformStatsRequest $request): PlatformStatsResource
    {
        return new PlatformStatsResource($this->reportQueryService->platformOverview());
    }
}

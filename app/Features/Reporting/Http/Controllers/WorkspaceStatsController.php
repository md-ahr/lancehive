<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Features\Reporting\Http\Requests\ShowWorkspaceStatsRequest;
use App\Features\Reporting\Http\Resources\WorkspaceStatsResource;
use App\Features\Reporting\Services\ReportQueryService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Reports', weight: 45)]
final class WorkspaceStatsController extends Controller
{
    public function __construct(private readonly ReportQueryService $reportQueryService) {}

    #[Endpoint(title: 'Workspace dashboard stats', description: 'Sync dashboard snapshot for the active workspace.')]
    public function show(ShowWorkspaceStatsRequest $request): WorkspaceStatsResource
    {
        return new WorkspaceStatsResource($this->reportQueryService->workspaceOverview());
    }
}

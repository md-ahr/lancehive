<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Features\Reporting\Actions\RunReportAction;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Http\Requests\RunReportRequest;
use App\Features\Reporting\Http\Resources\ReportRunResource;
use App\Features\Tenancy\Models\Freelancer;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Reports', weight: 45)]
final class RunReportController extends Controller
{
    public function __construct(
        private readonly RunReportAction $runReportAction,
        private readonly TenantContext $tenantContext,
    ) {}

    #[Endpoint(title: 'Run workspace report', description: 'Run an ad-hoc workspace report with summary and preview.')]
    public function store(RunReportRequest $request): ReportRunResource
    {
        $reportType = ReportType::from($request->string('report_type')->toString());
        $freelancer = Freelancer::query()->find($this->tenantContext->freelancerId());

        $result = $this->runReportAction->execute(
            $reportType,
            $request->input('filters', []),
            $request,
            tenantScoped: true,
            freelancer: $freelancer,
        );

        return new ReportRunResource($result);
    }
}

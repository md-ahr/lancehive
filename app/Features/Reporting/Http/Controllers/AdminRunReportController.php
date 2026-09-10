<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Features\Reporting\Actions\RunReportAction;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Http\Requests\AdminRunReportRequest;
use App\Features\Reporting\Http\Resources\ReportRunResource;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Reports', weight: 45)]
final class AdminRunReportController extends Controller
{
    public function __construct(private readonly RunReportAction $runReportAction) {}

    #[Endpoint(title: 'Run platform report', description: 'Run an ad-hoc platform report for super-admins.')]
    public function store(AdminRunReportRequest $request): ReportRunResource
    {
        $reportType = ReportType::from($request->string('report_type')->toString());

        $result = $this->runReportAction->execute(
            $reportType,
            $request->input('filters', []),
            $request,
            tenantScoped: false,
        );

        return new ReportRunResource($result);
    }
}

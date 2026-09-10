<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Core\Tenancy\TenantContext;
use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Http\Concerns\ResolvesScopedReports;
use App\Features\Reporting\Http\Requests\IndexReportExportRequest;
use App\Features\Reporting\Http\Requests\ShowReportExportRequest;
use App\Features\Reporting\Http\Requests\StoreReportExportRequest;
use App\Features\Reporting\Http\Resources\ReportExportResource;
use App\Features\Reporting\Jobs\GenerateReportExportJob;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Reporting\Services\ReportExportService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Reports', weight: 45)]
final class ReportExportController extends Controller
{
    use CursorPaginates;
    use ResolvesScopedReports;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReportExportService $exportService,
    ) {}

    public function index(IndexReportExportRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $query = ReportExport::query()
            ->forWorkspace($freelancerId)
            ->where('requested_by_user_id', $request->user()->id)
            ->orderByDesc('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = collect($page['data'])
            ->map(fn (ReportExport $export): array => (new ReportExportResource($this->withDownloadUrl($export)))->resolve())
            ->all();

        return response()->json($page);
    }

    public function store(StoreReportExportRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $reportType = ReportType::from($request->string('report_type')->toString());
        $format = ExportFormat::from($request->string('format')->toString());
        $savedReport = null;

        if ($request->filled('saved_report_id')) {
            $savedReport = SavedReport::query()
                ->forWorkspace($freelancerId)
                ->whereKey($request->integer('saved_report_id'))
                ->first();

            if ($savedReport === null) {
                throw new ApiException(ApiErrorCode::NotFound);
            }
        }

        $export = $this->exportService->createPendingExport(
            $request->user(),
            $reportType,
            $request->input('filters', []),
            $format,
            $freelancerId,
            $savedReport,
        );

        GenerateReportExportJob::dispatch($export->id);

        return (new ReportExportResource($export))
            ->response()
            ->setStatusCode(202);
    }

    public function show(ShowReportExportRequest $request, int $reportExport): ReportExportResource
    {
        $export = $this->resolveWorkspaceReportExport($reportExport);

        if (! $request->user()?->can('view', $export)) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        if ($export->isExpired()) {
            throw new ApiException(ApiErrorCode::ExportExpired);
        }

        return new ReportExportResource($this->withDownloadUrl($export));
    }

    private function withDownloadUrl(ReportExport $export): ReportExport
    {
        if ($export->status === ExportStatus::Completed && ! $export->isExpired() && $export->file_path !== null) {
            $export->setAttribute('download_url', URL::temporarySignedRoute(
                'report-exports.download',
                $export->expires_at,
                ['export' => $export->id],
            ));
        }

        return $export;
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Admin\Services\AdminActivityLogger;
use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Http\Concerns\ResolvesScopedReports;
use App\Features\Reporting\Http\Requests\AdminStoreReportExportRequest;
use App\Features\Reporting\Http\Requests\IndexReportExportRequest;
use App\Features\Reporting\Http\Requests\ShowReportExportRequest;
use App\Features\Reporting\Http\Resources\ReportExportResource;
use App\Features\Reporting\Jobs\GenerateReportExportJob;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Reporting\Services\ReportExportService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

#[Group('Reports', weight: 45)]
final class AdminReportExportController extends Controller
{
    use CursorPaginates;
    use ResolvesScopedReports;

    public function __construct(
        private readonly ReportExportService $exportService,
        private readonly AdminActivityLogger $activityLogger,
    ) {}

    public function index(IndexReportExportRequest $request): JsonResponse
    {
        $query = ReportExport::query()
            ->forPlatform()
            ->where('requested_by_user_id', $request->user()->id)
            ->orderByDesc('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = collect($page['data'])
            ->map(fn (ReportExport $export): array => (new ReportExportResource($this->withDownloadUrl($export)))->resolve())
            ->all();

        return response()->json($page);
    }

    public function store(AdminStoreReportExportRequest $request): JsonResponse
    {
        $reportType = ReportType::from($request->string('report_type')->toString());
        $format = ExportFormat::from($request->string('format')->toString());
        $savedReport = null;

        if ($request->filled('saved_report_id')) {
            $savedReport = SavedReport::query()
                ->forPlatform()
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
            freelancerId: null,
            savedReport: $savedReport,
        );

        GenerateReportExportJob::dispatch($export->id);

        $this->activityLogger->log(
            $request->user(),
            'report_export_queued',
            $export,
            ['report_type' => $reportType->value],
            $request,
        );

        return (new ReportExportResource($export))
            ->response()
            ->setStatusCode(202);
    }

    public function show(ShowReportExportRequest $request, int $reportExport): ReportExportResource
    {
        $export = $this->resolvePlatformReportExport($reportExport);

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
                'admin.report-exports.download',
                $export->expires_at,
                ['export' => $export->id],
            ));
        }

        return $export;
    }
}

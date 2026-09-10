<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Http\Concerns\ResolvesScopedReports;
use App\Features\Reporting\Http\Requests\DestroySavedReportRequest;
use App\Features\Reporting\Http\Requests\IndexSavedReportRequest;
use App\Features\Reporting\Http\Requests\ShowSavedReportRequest;
use App\Features\Reporting\Http\Requests\StoreSavedReportRequest;
use App\Features\Reporting\Http\Requests\UpdateSavedReportRequest;
use App\Features\Reporting\Http\Resources\SavedReportResource;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Reporting\Services\ReportFilterValidator;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Reports', weight: 45)]
final class SavedReportController extends Controller
{
    use CursorPaginates;
    use ResolvesScopedReports;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReportFilterValidator $filterValidator,
    ) {}

    public function index(IndexSavedReportRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $query = SavedReport::query()
            ->forWorkspace($freelancerId)
            ->orderByDesc('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = SavedReportResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreSavedReportRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $reportType = ReportType::from($request->string('report_type')->toString());
        $filters = $this->filterValidator->validateAndNormalize(
            $reportType,
            $request->input('filters', []),
            tenantScoped: true,
        );

        $report = SavedReport::query()->create([
            'freelancer_id' => $freelancerId,
            'created_by_user_id' => $request->user()->id,
            'name' => $request->string('name')->toString(),
            'report_type' => $reportType,
            'filters' => $filters,
        ]);

        return (new SavedReportResource($report))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowSavedReportRequest $request, int $savedReport): SavedReportResource
    {
        return new SavedReportResource($this->resolveWorkspaceSavedReport($savedReport));
    }

    public function update(UpdateSavedReportRequest $request, int $savedReport): SavedReportResource
    {
        $report = $this->resolveWorkspaceSavedReport($savedReport);
        $updates = [];

        if ($request->has('name')) {
            $updates['name'] = $request->string('name')->toString();
        }

        if ($request->has('filters')) {
            $updates['filters'] = $this->filterValidator->validateAndNormalize(
                $report->report_type,
                $request->input('filters', []),
                tenantScoped: true,
            );
        }

        if ($updates !== []) {
            $report->update($updates);
        }

        return new SavedReportResource($report->fresh());
    }

    public function destroy(DestroySavedReportRequest $request, int $savedReport): MessageResource
    {
        $this->resolveWorkspaceSavedReport($savedReport)->delete();

        return new MessageResource([
            'message' => 'Saved report deleted successfully.',
        ]);
    }
}

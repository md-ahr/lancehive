<?php

declare(strict_types=1);

namespace App\Features\Reporting\Actions;

use App\Core\Pagination\CursorPaginationResponse;
use App\Features\ClientBilling\Http\Resources\ClientInvoiceResource;
use App\Features\Delivery\Http\Resources\TimeLogResource;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Services\ReportFilterValidator;
use App\Features\Reporting\Services\ReportQueryService;
use App\Features\Tenancy\Http\Resources\FreelancerResource;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class RunReportAction
{
    public function __construct(
        private readonly ReportQueryService $reportQueryService,
        private readonly ReportFilterValidator $filterValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     report_type: string,
     *     filters: array<string, mixed>,
     *     summary: array<string, mixed>,
     *     preview: array<string, mixed>
     * }
     */
    public function execute(
        ReportType $reportType,
        array $filters,
        Request $request,
        bool $tenantScoped = true,
        ?Freelancer $freelancer = null,
    ): array {
        if (! $reportType->isRunnable()) {
            throw ValidationException::withMessages([
                'report_type' => ['The selected report type cannot be run via this endpoint.'],
            ]);
        }

        $normalizedFilters = $this->filterValidator->validateAndNormalize(
            $reportType,
            $filters,
            $tenantScoped,
        );

        $perPage = (int) $request->input('per_page', 25);

        if ($perPage < 1 || $perPage > 100) {
            throw ValidationException::withMessages([
                'per_page' => ['The per page field must be between 1 and 100.'],
            ]);
        }

        $summary = $this->buildSummary($reportType, $normalizedFilters, $freelancer);
        $preview = $this->buildPreview($reportType, $normalizedFilters, $request, $perPage);

        return [
            'report_type' => $reportType->value,
            'filters' => $normalizedFilters,
            'summary' => $summary,
            'preview' => $preview,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildSummary(ReportType $reportType, array $filters, ?Freelancer $freelancer): array
    {
        return match ($reportType) {
            ReportType::TimeLogs => $this->reportQueryService->timeLogsSummary($filters),
            ReportType::UnbilledWork => $this->reportQueryService->unbilledWorkSummary(
                $filters,
                $freelancer?->default_currency ?? 'BDT',
            ),
            ReportType::ClientInvoices => $this->reportQueryService->clientInvoicesSummary($filters),
            ReportType::FreelancerList => $this->reportQueryService->freelancerListSummary($filters),
            ReportType::SubscriptionRevenue => $this->reportQueryService->subscriptionRevenueSummary($filters),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildPreview(ReportType $reportType, array $filters, Request $request, int $perPage): array
    {
        $query = match ($reportType) {
            ReportType::TimeLogs => $this->reportQueryService->timeLogsQuery($filters),
            ReportType::UnbilledWork => $this->reportQueryService->unbilledWorkQuery($filters),
            ReportType::ClientInvoices => $this->reportQueryService->clientInvoicesQuery($filters),
            ReportType::FreelancerList => $this->reportQueryService->freelancerListQuery($filters),
            ReportType::SubscriptionRevenue => $this->reportQueryService->subscriptionChargesQuery($filters),
        };

        $paginator = $query->cursorPaginate($perPage)->withPath($request->url());
        $page = CursorPaginationResponse::from($paginator, $request);

        $page['data'] = match ($reportType) {
            ReportType::TimeLogs, ReportType::UnbilledWork => TimeLogResource::collection($paginator->items())->resolve(),
            ReportType::ClientInvoices => ClientInvoiceResource::collection($paginator->items())->resolve(),
            ReportType::FreelancerList => FreelancerResource::collection($paginator->items())->resolve(),
            ReportType::SubscriptionRevenue => collect($paginator->items())->map(fn ($charge): array => [
                'id' => $charge->id,
                'subscription_id' => $charge->subscription_id,
                'amount' => number_format((float) $charge->amount, 2, '.', ''),
                'currency' => $charge->currency,
                'status' => $charge->status?->value,
                'paid_at' => $charge->paid_at,
            ])->all(),
            default => $paginator->items(),
        };

        return $page;
    }
}

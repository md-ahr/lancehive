<?php

declare(strict_types=1);

namespace App\Features\Reporting\Services;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Models\SavedReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

final class ReportExportService
{
    public function __construct(
        private readonly ReportQueryService $reportQueryService,
        private readonly ReportFilterValidator $filterValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function createPendingExport(
        User $user,
        ReportType $reportType,
        array $filters,
        ExportFormat $format,
        ?int $freelancerId = null,
        ?SavedReport $savedReport = null,
    ): ReportExport {
        $tenantScoped = $freelancerId !== null;
        $normalizedFilters = $this->filterValidator->validateAndNormalize(
            $reportType,
            $filters,
            $tenantScoped,
        );

        return ReportExport::query()->create([
            'freelancer_id' => $freelancerId,
            'saved_report_id' => $savedReport?->id,
            'requested_by_user_id' => $user->id,
            'report_type' => $reportType,
            'filters' => $normalizedFilters,
            'format' => $format,
            'status' => ExportStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function processExport(ReportExport $export): void
    {
        if ($export->status === ExportStatus::Completed) {
            return;
        }

        $export->update(['status' => ExportStatus::Processing]);

        try {
            $path = $this->generateCsv($export);
            $rowCount = $this->countRows($export);

            $export->update([
                'status' => ExportStatus::Completed,
                'file_path' => $path,
                'row_count' => $rowCount,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            $export->update([
                'status' => ExportStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteExportFile(ReportExport $export): void
    {
        if ($export->file_path === null) {
            return;
        }

        Storage::disk('report-exports')->delete($export->file_path);
    }

    private function generateCsv(ReportExport $export): string
    {
        $reportType = $export->report_type;
        $filters = $export->filters ?? [];
        $filename = sprintf(
            'export-%d-%s.csv',
            $export->id,
            now()->format('YmdHis'),
        );

        $disk = Storage::disk('report-exports');
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open temporary stream for CSV export.');
        }

        $headers = $this->csvHeaders($reportType);
        fputcsv($handle, $headers);

        foreach ($this->exportRows($reportType, $filters) as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $disk->put($filename, stream_get_contents($handle) ?: '');
        fclose($handle);

        return $filename;
    }

    private function countRows(ReportExport $export): int
    {
        return $this->exportRows($export->report_type, $export->filters ?? [])->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, list<string|int|float|null>>
     */
    private function exportRows(ReportType $reportType, array $filters): LazyCollection
    {
        return match ($reportType) {
            ReportType::TimeLogs => $this->reportQueryService->timeLogsQuery($filters)
                ->lazy()
                ->map(fn ($log): array => [
                    $log->id,
                    $log->task_id,
                    $log->user_id,
                    number_format((float) $log->hours, 2, '.', ''),
                    $log->description,
                    $log->logged_at?->toDateString(),
                    $log->client_invoice_item_id,
                ]),
            ReportType::UnbilledWork => $this->reportQueryService->unbilledWorkQuery($filters)
                ->lazy()
                ->map(fn ($log): array => [
                    $log->id,
                    $log->task_id,
                    $log->user_id,
                    number_format((float) $log->hours, 2, '.', ''),
                    $log->description,
                    $log->logged_at?->toDateString(),
                ]),
            ReportType::ClientInvoices => $this->reportQueryService->clientInvoicesQuery($filters)
                ->lazy()
                ->map(fn ($invoice): array => [
                    $invoice->id,
                    $invoice->project_id,
                    $invoice->invoice_number,
                    $invoice->status?->value,
                    number_format((float) $invoice->total, 2, '.', ''),
                    $invoice->currency,
                    $invoice->issued_at?->toDateString(),
                ]),
            ReportType::FreelancerList => $this->reportQueryService->freelancerListQuery($filters)
                ->lazy()
                ->map(fn ($freelancer): array => [
                    $freelancer->id,
                    $freelancer->name,
                    $freelancer->slug,
                    $freelancer->status?->value,
                ]),
            ReportType::SubscriptionRevenue => $this->reportQueryService->subscriptionChargesQuery($filters)
                ->lazy()
                ->map(fn ($charge): array => [
                    $charge->id,
                    $charge->subscription_id,
                    number_format((float) $charge->amount, 2, '.', ''),
                    $charge->currency,
                    $charge->status?->value,
                    $charge->paid_at?->toDateTimeString(),
                ]),
            default => LazyCollection::make([]),
        };
    }

    /**
     * @return list<string>
     */
    private function csvHeaders(ReportType $reportType): array
    {
        return match ($reportType) {
            ReportType::TimeLogs => ['id', 'task_id', 'user_id', 'hours', 'description', 'logged_at', 'client_invoice_item_id'],
            ReportType::UnbilledWork => ['id', 'task_id', 'user_id', 'hours', 'description', 'logged_at'],
            ReportType::ClientInvoices => ['id', 'project_id', 'invoice_number', 'status', 'total', 'currency', 'issued_at'],
            ReportType::FreelancerList => ['id', 'name', 'slug', 'status'],
            ReportType::SubscriptionRevenue => ['id', 'subscription_id', 'amount', 'currency', 'status', 'paid_at'],
            default => [],
        };
    }
}

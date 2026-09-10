<?php

declare(strict_types=1);

use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Services\ReportExportService;
use Illuminate\Support\Facades\Storage;

it('creates pending export rows and writes csv without loading all rows', function () {
    Storage::fake('report-exports');
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    $export = app(ReportExportService::class)->createPendingExport(
        $workspace['user'],
        ReportType::TimeLogs,
        [],
        ExportFormat::Csv,
        $workspace['freelancer']->id,
    );

    expect($export->status)->toBe(ExportStatus::Pending);

    app(ReportExportService::class)->processExport($export);
    $export->refresh();

    expect($export->status)->toBe(ExportStatus::Completed)
        ->and($export->file_path)->not->toBeNull()
        ->and(Storage::disk('report-exports')->exists($export->file_path))->toBeTrue();
});

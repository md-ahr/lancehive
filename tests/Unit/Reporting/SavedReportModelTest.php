<?php

declare(strict_types=1);

use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Models\SavedReport;

it('casts saved report attributes', function () {
    $report = SavedReport::factory()->create([
        'report_type' => ReportType::TimeLogs,
        'filters' => ['from' => '2026-01-01'],
    ]);

    expect($report->report_type)->toBe(ReportType::TimeLogs)
        ->and($report->filters)->toBe(['from' => '2026-01-01']);
});

it('creates platform scoped saved reports with null freelancer id', function () {
    $report = SavedReport::factory()->platform()->create();

    expect($report->freelancer_id)->toBeNull()
        ->and($report->isPlatformScoped())->toBeTrue();
});

it('creates report exports for each export status', function () {
    foreach (ExportStatus::cases() as $status) {
        $export = ReportExport::factory()->state(['status' => $status])->create();
        expect($export->status)->toBe($status);
    }
});

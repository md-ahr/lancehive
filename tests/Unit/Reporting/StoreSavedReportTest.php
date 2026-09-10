<?php

declare(strict_types=1);

use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Reporting\Services\ReportFilterValidator;

it('creates a workspace saved report with normalized filters', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    $filters = app(ReportFilterValidator::class)->validateAndNormalize(
        ReportType::TimeLogs,
        ['from' => '2026-01-01'],
        tenantScoped: true,
    );

    $report = SavedReport::query()->create([
        'freelancer_id' => $workspace['freelancer']->id,
        'created_by_user_id' => $workspace['user']->id,
        'name' => 'Monthly logs',
        'report_type' => ReportType::TimeLogs,
        'filters' => $filters,
    ]);

    expect($report->name)->toBe('Monthly logs')
        ->and($report->filters)->toBe(['from' => '2026-01-01']);
});

<?php

declare(strict_types=1);

use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Jobs\GenerateReportExportJob;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Services\ReportExportService;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use Illuminate\Support\Facades\Storage;

it('processes export job and marks completed', function () {
    Storage::fake('report-exports');
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Owner);
    $export = ReportExport::factory()
        ->for($workspace['freelancer'])
        ->pending()
        ->create([
            'requested_by_user_id' => $workspace['user']->id,
        ]);

    (new GenerateReportExportJob($export->id))->handle(app(ReportExportService::class));

    $export->refresh();
    expect($export->status)->toBe(ExportStatus::Completed)
        ->and($export->file_path)->not->toBeNull();
});

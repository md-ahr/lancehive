<?php

declare(strict_types=1);

use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Jobs\GenerateReportExportJob;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use Illuminate\Support\Facades\Queue;

it('queues a report export for owner', function () {
    Queue::fake();
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Owner);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('report-exports'), [
            'report_type' => 'time_logs',
            'format' => 'csv',
            'filters' => [],
        ])
        ->assertAccepted()
        ->assertJsonPath('status', ExportStatus::Pending->value);

    Queue::assertPushed(GenerateReportExportJob::class);

    expect(ReportExport::query()->count())->toBe(1);
});

it('forbids members from queueing report exports', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('report-exports'), [
            'report_type' => 'time_logs',
            'format' => 'csv',
            'filters' => [],
        ])
        ->assertForbidden();
});

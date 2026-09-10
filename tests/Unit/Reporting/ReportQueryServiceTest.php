<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Reporting\Services\ReportQueryService;
use App\Features\Tenancy\Enums\FreelancerStatus;

it('returns zero workspace overview stats for an empty workspace', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    $stats = app(ReportQueryService::class)->workspaceOverview();

    expect($stats)->toMatchArray([
        'active_clients' => 0,
        'active_projects' => 0,
        'hours_this_month' => '0.00',
        'unbilled_hours' => '0.00',
        'outstanding_invoice_total' => '0.00',
    ]);
});

it('aggregates workspace overview stats via sql', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    TimeLog::factory()->for($task)->create([
        'hours' => '3.00',
        'logged_at' => now(),
        'client_invoice_item_id' => null,
    ]);

    $invoice = ClientInvoice::factory()->for($project)->for($workspace['freelancer'])->create([
        'status' => ClientInvoiceStatus::Sent,
        'total' => '100.00',
    ]);

    $stats = app(ReportQueryService::class)->workspaceOverview();

    expect($stats['active_clients'])->toBe(1)
        ->and($stats['active_projects'])->toBe(1)
        ->and($stats['hours_this_month'])->toBe('3.00')
        ->and($stats['unbilled_hours'])->toBe('3.00')
        ->and($stats['outstanding_invoice_total'])->toBe('100.00');
});

it('returns platform overview counts by freelancer status', function () {
    $this->createTenantWorkspace([], ['status' => FreelancerStatus::Active]);
    $this->createTenantWorkspace([], ['status' => FreelancerStatus::Suspended]);

    $stats = app(ReportQueryService::class)->platformOverview();

    expect($stats['freelancers_by_status']['active'])->toBeGreaterThanOrEqual(1)
        ->and($stats['freelancers_by_status']['suspended'])->toBeGreaterThanOrEqual(1);
});

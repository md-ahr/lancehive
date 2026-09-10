<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Reporting\Models\SavedReport;
use Laravel\Sanctum\Sanctum;

it('returns not found for cross tenant saved report show', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $report = SavedReport::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("reports/{$report->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when running report with another workspaces client id', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $foreignClient = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->postJson($this->apiUrl('reports/run'), [
            'report_type' => 'time_logs',
            'filters' => ['client_id' => $foreignClient->id],
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('forbids non admin from admin report routes', function () {
    $workspace = $this->createTenantWorkspace();
    Sanctum::actingAs($workspace['user']);

    $this->getJson($this->apiUrl('admin/reports/platform-stats'))
        ->assertForbidden();

    $this->postJson($this->apiUrl('admin/reports/run'), [
        'report_type' => 'freelancer_list',
        'filters' => [],
    ])
        ->assertForbidden();
});

it('forbids tenant user from admin saved reports', function () {
    $workspace = $this->createTenantWorkspace();
    Sanctum::actingAs($workspace['user']);

    $this->getJson($this->apiUrl('admin/reports'))
        ->assertForbidden();
});

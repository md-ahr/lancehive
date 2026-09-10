<?php

declare(strict_types=1);

use App\Features\Reporting\Models\SavedReport;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('creates updates and deletes a saved report for owner', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Owner);

    $createResponse = $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('reports'), [
            'name' => 'Weekly logs',
            'report_type' => 'time_logs',
            'filters' => ['from' => '2026-01-01'],
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Weekly logs');

    $reportId = $createResponse->json('id');

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("reports/{$reportId}"), [
            'name' => 'Monthly logs',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Monthly logs');

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("reports/{$reportId}"))
        ->assertOk();

    expect(SavedReport::query()->find($reportId))->toBeNull();
});

it('forbids members from creating saved reports', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('reports'), [
            'name' => 'Blocked report',
            'report_type' => 'time_logs',
            'filters' => [],
        ])
        ->assertForbidden();
});

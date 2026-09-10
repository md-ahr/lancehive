<?php

declare(strict_types=1);

use App\Features\Reporting\Models\ReportExport;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('returns export expired for expired completed export', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Owner);
    $export = ReportExport::factory()
        ->for($workspace['freelancer'])
        ->completed()
        ->expired()
        ->create([
            'requested_by_user_id' => $workspace['user']->id,
        ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("report-exports/{$export->id}"))
        ->assertStatus(410)
        ->assertJsonPath('code', 'export_expired');
});

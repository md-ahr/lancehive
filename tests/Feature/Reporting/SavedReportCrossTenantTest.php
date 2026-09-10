<?php

declare(strict_types=1);

use App\Features\Reporting\Models\SavedReport;

it('returns not found for cross tenant saved report', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $report = SavedReport::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("reports/{$report->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

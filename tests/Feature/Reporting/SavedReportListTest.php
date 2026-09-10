<?php

declare(strict_types=1);

use App\Features\Reporting\Models\SavedReport;

it('lists saved reports for workspace members', function () {
    $workspace = $this->createTenantWorkspace();
    SavedReport::factory()
        ->for($workspace['freelancer'])
        ->create(['name' => 'Monthly logs', 'created_by_user_id' => $workspace['user']->id]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('reports'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Monthly logs');
});

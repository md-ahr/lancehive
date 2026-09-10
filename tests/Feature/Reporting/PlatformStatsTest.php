<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns platform stats for super admin', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->getJson($this->apiUrl('admin/reports/platform-stats'))
        ->assertOk()
        ->assertJsonStructure([
            'freelancers_by_status',
            'subscriptions_by_plan',
            'subscriptions_by_status',
            'trials_ending_soon',
        ]);
});

it('forbids platform stats for non admin', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('admin/reports/platform-stats'))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

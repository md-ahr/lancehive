<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('forbids non admin from admin report routes', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('admin/reports/run'), [
            'report_type' => 'freelancer_list',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

it('forbids tenant user from admin saved reports', function () {
    Sanctum::actingAs(User::factory()->user()->create());

    $this->getJson($this->apiUrl('admin/reports'))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

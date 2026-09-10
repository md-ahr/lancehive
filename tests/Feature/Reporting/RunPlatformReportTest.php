<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('runs platform report for super admin', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->postJson($this->apiUrl('admin/reports/run'), [
        'report_type' => 'freelancer_list',
        'filters' => [],
    ])
        ->assertOk()
        ->assertJsonPath('report_type', 'freelancer_list')
        ->assertJsonStructure(['summary', 'preview']);
});

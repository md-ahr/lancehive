<?php

declare(strict_types=1);

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use Laravel\Sanctum\Sanctum;

it('updates freelancer status for super admin', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $freelancer = Freelancer::factory()->pending()->create();

    $this->patchJson($this->apiUrl("admin/freelancers/{$freelancer->id}"), [
        'status' => 'active',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'active');

    expect($freelancer->fresh()->status)->toBe(FreelancerStatus::Active);
});

it('logs admin activity when freelancer is suspended', function () {
    $admin = User::factory()->superAdmin()->create();
    Sanctum::actingAs($admin);

    $freelancer = Freelancer::factory()->active()->create();

    $this->patchJson($this->apiUrl("admin/freelancers/{$freelancer->id}"), [
        'status' => 'suspended',
    ])->assertOk();

    expect(AdminActivityLog::query()
        ->where('admin_user_id', $admin->id)
        ->where('action', 'freelancer.suspend')
        ->where('target_id', $freelancer->id)
        ->exists())->toBeTrue();
});

it('returns validation error for invalid status', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $freelancer = Freelancer::factory()->active()->create();

    $this->patchJson($this->apiUrl("admin/freelancers/{$freelancer->id}"), [
        'status' => 'invalid',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

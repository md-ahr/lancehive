<?php

declare(strict_types=1);

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Models\Freelancer;
use Laravel\Sanctum\Sanctum;

it('returns freelancer detail for super admin', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create(['name' => 'Starter']);
    Subscription::factory()->for($freelancer)->for($plan)->create();

    $this->getJson($this->apiUrl("admin/freelancers/{$freelancer->id}"))
        ->assertOk()
        ->assertJsonPath('id', $freelancer->id)
        ->assertJsonPath('owner.id', $freelancer->owner_user_id)
        ->assertJsonPath('subscription_summary.plan_name', 'Starter');
});

it('returns not found for unknown freelancer id', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->getJson($this->apiUrl('admin/freelancers/99999'))
        ->assertNotFound();
});

it('logs admin activity when super admin uses freelancer_id override on show', function () {
    $admin = User::factory()->superAdmin()->create();
    Sanctum::actingAs($admin);

    $freelancer = Freelancer::factory()->active()->create();

    $this->getJson($this->apiUrl("admin/freelancers/{$freelancer->id}?freelancer_id={$freelancer->id}"))
        ->assertOk();

    expect(AdminActivityLog::query()
        ->where('admin_user_id', $admin->id)
        ->where('action', 'freelancer.override')
        ->where('target_id', $freelancer->id)
        ->exists())->toBeTrue();
});

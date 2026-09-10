<?php

declare(strict_types=1);

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Models\Freelancer;
use Laravel\Sanctum\Sanctum;

it('allows super admin to list freelancers', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());
    Freelancer::factory()->count(2)->active()->create();

    $this->getJson($this->apiUrl('admin/freelancers'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'status'],
            ],
            'links',
            'meta',
        ]);
});

it('denies regular users from admin freelancer routes', function () {
    Sanctum::actingAs(User::factory()->freelancer()->create());

    $this->getJson($this->apiUrl('admin/freelancers'))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

it('allows super admin without membership to access tenant client routes with header', function () {
    $workspaceB = $this->createTenantWorkspace();
    Client::factory()->for($workspaceB['freelancer'])->create(['name' => 'Workspace B Client']);
    $admin = User::factory()->superAdmin()->create();

    Sanctum::actingAs($admin);

    $this->withHeader('X-Freelancer-Id', (string) $workspaceB['freelancer']->id)
        ->getJson($this->apiUrl('clients'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Workspace B Client');
});

it('ignores freelancer_id query override for regular users on tenant routes', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("clients/{$client->id}?freelancer_id={$workspaceB['freelancer']->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('denies regular users from using freelancer_id override on admin routes', function () {
    $freelancer = Freelancer::factory()->active()->create();

    Sanctum::actingAs(User::factory()->freelancer()->create());

    $this->getJson($this->apiUrl("admin/freelancers/{$freelancer->id}?freelancer_id={$freelancer->id}"))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

it('logs admin activity when super admin uses freelancer_id override on admin show', function () {
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

<?php

declare(strict_types=1);

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Route::middleware(['api', 'auth:sanctum', 'freelancer.context'])
        ->get($this->apiUrl('__test/tenant-context'), function () {
            return response()->json([
                'freelancer_id' => app(TenantContext::class)->freelancerId(),
            ]);
        });
});

it('allows a member with a valid X-Freelancer-Id header', function () {
    $workspace = $this->createTenantWorkspace();

    Sanctum::actingAs($workspace['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspace['freelancer']->id)
        ->getJson($this->apiUrl('__test/tenant-context'))
        ->assertOk()
        ->assertJsonPath('freelancer_id', $workspace['freelancer']->id);
});

it('auto-selects the workspace when the user has a single membership', function () {
    $workspace = $this->createTenantWorkspace();

    Sanctum::actingAs($workspace['user']);

    $this->getJson($this->apiUrl('__test/tenant-context'))
        ->assertOk()
        ->assertJsonPath('freelancer_id', $workspace['freelancer']->id);
});

it('rejects users who are not members of the requested workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();

    Sanctum::actingAs($workspaceA['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspaceB['freelancer']->id)
        ->getJson($this->apiUrl('__test/tenant-context'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('requires a header when the user belongs to multiple workspaces', function () {
    $user = User::factory()->freelancer()->create();
    $first = Freelancer::factory()->active()->create(['owner_user_id' => $user->id]);
    $second = Freelancer::factory()->active()->create();

    FreelancerMembership::factory()->for($first)->for($user)->owner()->create();
    FreelancerMembership::factory()->for($second)->for($user)->member()->create();

    Sanctum::actingAs($user);

    $this->getJson($this->apiUrl('__test/tenant-context'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('rejects suspended workspaces', function () {
    $workspace = $this->createTenantWorkspace(freelancerAttributes: ['status' => FreelancerStatus::Suspended]);

    Sanctum::actingAs($workspace['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspace['freelancer']->id)
        ->getJson($this->apiUrl('__test/tenant-context'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Settings\Cache\WorkspaceSettingsCache;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Laravel\Sanctum\Sanctum;

it('allows workspace members to read settings', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $workspace['freelancer']->update([
        'default_currency' => 'USD',
        'business_name' => 'Acme Studio',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('workspace/settings'))
        ->assertOk()
        ->assertJsonPath('default_currency', 'USD')
        ->assertJsonPath('business_name', 'Acme Studio');
});

it('allows owners to patch workspace settings', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl('workspace/settings'), [
            'invoice_number_prefix' => 'ACME',
            'default_currency' => 'EUR',
        ])
        ->assertOk()
        ->assertJsonPath('invoice_number_prefix', 'ACME')
        ->assertJsonPath('default_currency', 'EUR');
});

it('refreshes workspace settings cache after patch', function () {
    $workspace = $this->createTenantWorkspace();
    $cache = app(WorkspaceSettingsCache::class);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('workspace/settings'))
        ->assertOk();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl('workspace/settings'), [
            'business_name' => 'Updated Studio',
        ])
        ->assertOk()
        ->assertJsonPath('business_name', 'Updated Studio');

    expect($cache->get($workspace['freelancer']->id)['business_name'])->toBe('Updated Studio');
});

it('denies members from patching workspace settings', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl('workspace/settings'), [
            'invoice_number_prefix' => 'NOPE',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('blocks workspace settings patch when subscription is read only', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->expiredTrial()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl('workspace/settings'), [
            'business_name' => 'Blocked',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'workspace_read_only');
});

it('returns validation error for invalid invoice prefix', function () {
    $workspace = $this->createTenantWorkspace();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl('workspace/settings'), [
            'invoice_number_prefix' => 'BAD PREFIX!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['invoice_number_prefix']);
});

it('requires freelancer header for multi-membership users', function () {
    $user = $this->createTenantWorkspace()['user'];
    $secondFreelancer = Freelancer::factory()->active()->create();
    FreelancerMembership::factory()->for($secondFreelancer)->for($user)->member()->create();

    Sanctum::actingAs($user);

    $this->getJson($this->apiUrl('workspace/settings'))
        ->assertForbidden();
});

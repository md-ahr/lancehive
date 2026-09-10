<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Cache\PlanCache;
use App\Features\PlatformBilling\Models\Plan;
use Laravel\Sanctum\Sanctum;

it('lists plans for super admin', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());
    Plan::factory()->count(2)->create();

    $this->getJson($this->apiUrl('admin/plans'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('denies plan management for non admin', function () {
    Sanctum::actingAs(User::factory()->user()->create());

    $this->getJson($this->apiUrl('admin/plans'))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});

it('creates a plan and invalidates cache', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->postJson($this->apiUrl('admin/plans'), [
        'name' => 'Enterprise',
        'slug' => 'enterprise',
        'price_monthly' => 1500,
        'max_clients' => 50,
    ])
        ->assertCreated()
        ->assertJsonPath('slug', 'enterprise');

    expect(Plan::query()->where('slug', 'enterprise')->exists())->toBeTrue();
});

it('deactivates a plan and excludes it from active plan cache', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());
    $plan = Plan::factory()->create(['is_active' => true, 'sort_order' => 10]);
    $active = Plan::factory()->create(['is_active' => true, 'sort_order' => 20]);

    app(PlanCache::class)->activePlans();

    $this->patchJson($this->apiUrl('admin/plans/'.$plan->id), [
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('is_active', false);

    $cachedSlugs = app(PlanCache::class)
        ->activePlans()
        ->pluck('slug')
        ->all();

    expect($cachedSlugs)->toContain($active->slug)
        ->and($cachedSlugs)->not->toContain($plan->slug);
});

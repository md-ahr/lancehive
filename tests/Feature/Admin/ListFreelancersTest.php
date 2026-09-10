<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\Freelancer;
use Laravel\Sanctum\Sanctum;

it('returns empty data array when platform has no freelancers', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->getJson($this->apiUrl('admin/freelancers'))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ]);
});

it('returns paginated freelancers for super admin', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    Freelancer::factory()->count(2)->create();

    $this->getJson($this->apiUrl('admin/freelancers'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'status', 'owner_user_id', 'created_at', 'updated_at'],
            ],
        ]);
});

it('filters freelancers by status', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    Freelancer::factory()->active()->create();
    Freelancer::factory()->suspended()->create();

    $this->getJson($this->apiUrl('admin/freelancers?status=active'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'active');
});

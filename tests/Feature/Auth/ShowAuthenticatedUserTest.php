<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to users endpoint', function () {
    $this->getJson($this->apiUrl('users'))
        ->assertUnauthorized();
});

it('denies client access to users endpoint', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson($this->apiUrl('users'))
        ->assertForbidden();
});

it('denies freelancer access to users endpoint', function () {
    Sanctum::actingAs(User::factory()->freelancer()->create());

    $this->getJson($this->apiUrl('users'))
        ->assertForbidden();
});

it('allows super admin to list all users with cursor pagination', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $freelancer = User::factory()->freelancer()->create();
    $client = User::factory()->client()->create();

    Sanctum::actingAs($superAdmin);

    $this->getJson($this->apiUrl('users'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'role'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment([
            'id' => $superAdmin->id,
            'email' => $superAdmin->email,
            'role' => UserRole::SuperAdmin->value,
        ])
        ->assertJsonFragment([
            'id' => $freelancer->id,
            'role' => UserRole::User->value,
        ])
        ->assertJsonFragment([
            'id' => $client->id,
            'role' => UserRole::User->value,
        ]);
});

it('allows super admin to list all users with bearer token', function () {
    User::factory()->freelancer()->create();
    User::factory()->client()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $token = $superAdmin->createToken('test-token')->plainTextToken;

    $this->withToken($token)
        ->getJson($this->apiUrl('users'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('rejects per_page above maximum for users list', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->getJson($this->apiUrl('users').'?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

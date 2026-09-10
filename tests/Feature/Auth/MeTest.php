<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to me endpoint', function () {
    $this->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

it('allows authenticated freelancer to access me endpoint', function () {
    $freelancer = User::factory()->freelancer()->create([
        'email' => 'freelancer@example.com',
    ]);

    Sanctum::actingAs($freelancer);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.id', $freelancer->id)
        ->assertJsonPath('user.email', 'freelancer@example.com')
        ->assertJsonPath('user.role', UserRole::Freelancer->value);
});

it('allows authenticated client to access me endpoint', function () {
    $client = User::factory()->client()->create([
        'email' => 'client@example.com',
    ]);

    Sanctum::actingAs($client);

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.id', $client->id)
        ->assertJsonPath('user.email', 'client@example.com')
        ->assertJsonPath('user.role', UserRole::Client->value);
});

it('allows me endpoint access with bearer token from login', function () {
    User::factory()->client()->create([
        'email' => 'client@example.com',
        'password' => 'password',
    ]);

    $token = $this->postJson($this->apiUrl('login'), [
        'email' => 'client@example.com',
        'password' => 'password',
    ])->json('token');

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.email', 'client@example.com')
        ->assertJsonPath('user.role', UserRole::Client->value);
});

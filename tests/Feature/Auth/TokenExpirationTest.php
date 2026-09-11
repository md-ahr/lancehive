<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects expired bearer token', function () {
    config(['sanctum.expiration' => 1]);

    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->travel(2)->minutes();

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

it('allows valid bearer token before expiration', function () {
    config(['sanctum.expiration' => 60]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson($this->apiUrl('me'))
        ->assertOk();
});

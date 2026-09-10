<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;

it('denies logout for unauthenticated user', function () {
    $this->postJson($this->apiUrl('logout'))
        ->assertUnauthorized();
});

it('allows authenticated user to logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson($this->apiUrl('logout'))
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');
});

it('revokes token after logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withToken($token)->postJson($this->apiUrl('logout'))->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 0);

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

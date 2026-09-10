<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('resets password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'old-password',
    ]);

    $token = $user->createToken('api-token')->plainTextToken;
    $resetToken = Password::broker()->createToken($user);

    $this->postJson($this->apiUrl('reset-password'), [
        'email' => 'user@example.com',
        'token' => $resetToken,
        'password' => 'newpassword1',
        'password_confirmation' => 'newpassword1',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Password reset successfully.');

    $user->refresh();

    expect(Hash::check('newpassword1', $user->password))->toBeTrue();
    expect(Hash::check('old-password', $user->password))->toBeFalse();
    $this->assertDatabaseCount('personal_access_tokens', 0);

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

it('fails reset password with invalid token', function () {
    User::factory()->create(['email' => 'user@example.com']);

    $this->postJson($this->apiUrl('reset-password'), [
        'email' => 'user@example.com',
        'token' => 'invalid-token',
        'password' => 'newpassword1',
        'password_confirmation' => 'newpassword1',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires matching password confirmation on reset', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    $resetToken = Password::broker()->createToken($user);

    $this->postJson($this->apiUrl('reset-password'), [
        'email' => 'user@example.com',
        'token' => $resetToken,
        'password' => 'newpassword1',
        'password_confirmation' => 'differentpassword1',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects weak password on reset', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    $resetToken = Password::broker()->createToken($user);

    $this->postJson($this->apiUrl('reset-password'), [
        'email' => 'user@example.com',
        'token' => $resetToken,
        'password' => 'short',
        'password_confirmation' => 'short',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires all fields for reset password', function () {
    $this->postJson($this->apiUrl('reset-password'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token', 'email', 'password']);
});

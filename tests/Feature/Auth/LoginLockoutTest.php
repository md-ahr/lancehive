<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;

beforeEach(function () {
    config([
        'security.login.max_attempts' => 3,
        'security.login.lockout_minutes' => 15,
    ]);
});

it('locks account after max failed login attempts', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    foreach (range(1, 3) as $attempt) {
        $this->postJson($this->apiUrl('login'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    expect(User::query()->where('email', 'user@example.com')->first())
        ->locked_until->not->toBeNull();
});

it('returns the same validation error when account is locked', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $user->forceFill([
        'failed_login_attempts' => 3,
        'locked_until' => now()->addMinutes(15),
    ])->save();

    $this->postJson($this->apiUrl('login'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
});

it('clears lockout after the lock window and allows login', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    foreach (range(1, 3) as $attempt) {
        $this->postJson($this->apiUrl('login'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->travel(16)->minutes();

    $this->postJson($this->apiUrl('login'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user']);

    $user = User::query()->where('email', 'user@example.com')->firstOrFail();
    expect($user->failed_login_attempts)->toBe(0)
        ->and($user->locked_until)->toBeNull();
});

it('resets failed attempts on successful login', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $user->forceFill(['failed_login_attempts' => 2])->save();

    $this->postJson($this->apiUrl('login'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->assertOk();

    expect($user->fresh())
        ->failed_login_attempts->toBe(0)
        ->locked_until->toBeNull();
});

<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;

it('does not mass assign role on create', function () {
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'mass-assign@example.com',
        'password' => 'password',
        'role' => UserRole::SuperAdmin,
    ]);

    $user->refresh();

    expect($user->role)->toBe(UserRole::User);
});

it('allows setting role via forceFill on trusted paths', function () {
    $user = User::factory()->create();

    $user->forceFill(['role' => UserRole::SuperAdmin])->saveQuietly();

    expect($user->fresh()->role)->toBe(UserRole::SuperAdmin);
});

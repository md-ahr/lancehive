<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;

it('casts user role to enum', function () {
    $user = User::factory()->freelancer()->create();

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::Freelancer);
});

it('isFreelancer returns true for freelancer role', function () {
    $user = User::factory()->freelancer()->create();

    expect($user->isFreelancer())->toBeTrue();
    expect($user->isClient())->toBeFalse();
});

it('isClient returns true for client role', function () {
    $user = User::factory()->client()->create();

    expect($user->isClient())->toBeTrue();
    expect($user->isFreelancer())->toBeFalse();
    expect($user->isSuperAdmin())->toBeFalse();
});

it('isSuperAdmin returns true for super admin role', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->isSuperAdmin())->toBeTrue();
    expect($user->isFreelancer())->toBeFalse();
    expect($user->isClient())->toBeFalse();
});

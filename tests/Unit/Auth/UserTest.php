<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Tenancy\Models\FreelancerMembership;

it('casts user role to enum', function () {
    $user = User::factory()->user()->create();

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::User);
});

it('isFreelancer returns true when user has freelancer membership', function () {
    $user = User::factory()->user()->create();
    FreelancerMembership::factory()->for($user)->create();

    expect($user->isFreelancer())->toBeTrue();
    expect($user->isClient())->toBeFalse();
});

it('isClient returns true when user has client membership', function () {
    $user = User::factory()->user()->create();
    ClientMembership::factory()->for($user)->create();

    expect($user->isClient())->toBeTrue();
    expect($user->isFreelancer())->toBeFalse();
    expect($user->isSuperAdmin())->toBeFalse();
});

it('isSuperAdmin returns true for super admin role', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->isSuperAdmin())->toBeTrue();
    expect($user->isUser())->toBeFalse();
    expect($user->isFreelancer())->toBeFalse();
    expect($user->isClient())->toBeFalse();
});

it('isUser returns true for standard platform role', function () {
    $user = User::factory()->user()->create();

    expect($user->isUser())->toBeTrue();
    expect($user->isSuperAdmin())->toBeFalse();
});

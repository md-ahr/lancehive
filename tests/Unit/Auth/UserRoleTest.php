<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;

dataset('roles', [
    'super admin' => [UserRole::SuperAdmin, true, false, false, false],
    'user' => [UserRole::User, false, true, false, false],
    'freelancer legacy' => [UserRole::Freelancer, false, false, true, false],
    'client legacy' => [UserRole::Client, false, false, false, true],
]);

it('role helpers return correct values', function (
    UserRole $role,
    bool $isSuperAdmin,
    bool $isUser,
    bool $isFreelancer,
    bool $isClient,
) {
    expect($role->isSuperAdmin())->toBe($isSuperAdmin);
    expect($role->isUser())->toBe($isUser);
    expect($role->isFreelancer())->toBe($isFreelancer);
    expect($role->isClient())->toBe($isClient);
})->with('roles');

it('values returns all role strings', function () {
    expect(UserRole::values())->toBe(['super_admin', 'user', 'freelancer', 'client']);
});

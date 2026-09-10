<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;

dataset('roles', [
    'super admin' => [UserRole::SuperAdmin, true, false, false],
    'freelancer' => [UserRole::Freelancer, false, true, false],
    'client' => [UserRole::Client, false, false, true],
]);

it('role helpers return correct values', function (
    UserRole $role,
    bool $isSuperAdmin,
    bool $isFreelancer,
    bool $isClient,
) {
    expect($role->isSuperAdmin())->toBe($isSuperAdmin);
    expect($role->isFreelancer())->toBe($isFreelancer);
    expect($role->isClient())->toBe($isClient);
})->with('roles');

it('values returns all role strings', function () {
    expect(UserRole::values())->toBe(['super_admin', 'freelancer', 'client']);
});

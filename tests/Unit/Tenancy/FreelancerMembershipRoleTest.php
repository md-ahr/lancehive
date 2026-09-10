<?php

declare(strict_types=1);

use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;

it('exposes all membership role values', function () {
    expect(FreelancerMembershipRole::values())->toBe(['owner', 'admin', 'member']);
});

it('round trips membership role through the model cast', function () {
    $membership = FreelancerMembership::factory()->create([
        'role' => FreelancerMembershipRole::Admin,
    ]);

    expect($membership->role)->toBe(FreelancerMembershipRole::Admin)
        ->and($membership->getAttributes()['role'])->toBe('admin');
});

<?php

declare(strict_types=1);

use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;

it('exposes all membership role values', function () {
    expect(FreelancerMembershipRole::values())->toBe(['owner', 'admin', 'member']);
});

it('defines role capability helpers', function () {
    expect(FreelancerMembershipRole::Owner->canManageTeam())->toBeTrue()
        ->and(FreelancerMembershipRole::Admin->canManageClientsAndProjects())->toBeTrue()
        ->and(FreelancerMembershipRole::Member->canManageInvoices())->toBeFalse()
        ->and(FreelancerMembershipRole::Owner->canRemoveMember(FreelancerMembershipRole::Member))->toBeTrue()
        ->and(FreelancerMembershipRole::Admin->canRemoveMember(FreelancerMembershipRole::Admin))->toBeFalse()
        ->and(FreelancerMembershipRole::Member->isInvitable())->toBeTrue()
        ->and(FreelancerMembershipRole::Owner->isInvitable())->toBeFalse();
});

it('round trips membership role through the model cast', function () {
    $membership = FreelancerMembership::factory()->create([
        'role' => FreelancerMembershipRole::Admin,
    ]);

    expect($membership->role)->toBe(FreelancerMembershipRole::Admin)
        ->and($membership->getAttributes()['role'])->toBe('admin');
});

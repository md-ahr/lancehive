<?php

declare(strict_types=1);

use App\Features\Tenancy\Models\FreelancerMembership;

it('links freelancer and user', function () {
    $membership = FreelancerMembership::factory()->create();

    expect($membership->freelancer)->not->toBeNull()
        ->and($membership->user)->not->toBeNull()
        ->and($membership->freelancer->memberships->contains($membership))->toBeTrue();
});

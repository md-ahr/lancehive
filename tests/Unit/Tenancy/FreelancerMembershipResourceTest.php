<?php

declare(strict_types=1);

use App\Features\Tenancy\Http\Resources\FreelancerMembershipResource;
use App\Features\Tenancy\Models\FreelancerMembership;

it('serializes freelancer membership resource with role and freelancer summary', function () {
    $membership = FreelancerMembership::factory()->owner()->create();
    $membership->load('freelancer');

    $payload = json_decode((new FreelancerMembershipResource($membership))->toJson(), true);

    expect($payload)->toHaveKeys([
        'id',
        'freelancer_id',
        'user_id',
        'role',
        'freelancer',
        'created_at',
        'updated_at',
    ])
        ->and($payload['role'])->toBe('owner')
        ->and($payload['freelancer'])->toBeArray()
        ->and(array_keys($payload['freelancer']))->toContain('id', 'name', 'slug', 'status');
});

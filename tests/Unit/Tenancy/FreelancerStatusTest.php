<?php

declare(strict_types=1);

use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;

it('exposes all freelancer status values', function () {
    expect(FreelancerStatus::values())->toBe(['pending', 'active', 'suspended']);
});

it('round trips freelancer status through the model cast', function () {
    $freelancer = Freelancer::factory()->pending()->create();

    expect($freelancer->status)->toBe(FreelancerStatus::Pending)
        ->and($freelancer->getAttributes()['status'])->toBe('pending');
});

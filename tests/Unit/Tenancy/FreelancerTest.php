<?php

declare(strict_types=1);

use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

it('casts status to enum and links owner', function () {
    $freelancer = Freelancer::factory()->active()->create();

    expect($freelancer->status)->toBe(FreelancerStatus::Active)
        ->and($freelancer->owner)->not->toBeNull()
        ->and($freelancer->owner->id)->toBe($freelancer->owner_user_id);
});

it('supports factory states', function () {
    expect(Freelancer::factory()->pending()->create()->status)->toBe(FreelancerStatus::Pending);
    expect(Freelancer::factory()->suspended()->create()->status)->toBe(FreelancerStatus::Suspended);
});

it('relates to clients projects memberships and subscription', function () {
    $freelancer = Freelancer::factory()->create();

    expect($freelancer->clients())->toBeInstanceOf(HasMany::class)
        ->and($freelancer->projects())->toBeInstanceOf(HasMany::class)
        ->and($freelancer->memberships())->toBeInstanceOf(HasMany::class)
        ->and($freelancer->subscription())->toBeInstanceOf(HasOne::class);
});

<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Http\Resources\FreelancerDetailResource;
use App\Features\Tenancy\Http\Resources\FreelancerResource;
use App\Features\Tenancy\Models\Freelancer;

it('serializes freelancer resource with expected keys', function () {
    $freelancer = Freelancer::factory()->active()->create();

    $payload = (new FreelancerResource($freelancer))->resolve();

    expect($payload)->toHaveKeys([
        'id',
        'name',
        'slug',
        'status',
        'owner_user_id',
        'created_at',
        'updated_at',
    ])
        ->and($payload['status'])->toBe('active');
});

it('serializes freelancer detail resource with nested summary fields', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create(['name' => 'Starter']);

    Subscription::factory()->for($freelancer)->for($plan)->create();

    $freelancer->load(['owner', 'subscription.plan']);
    $freelancer->loadCount('memberships');

    $payload = (new FreelancerDetailResource($freelancer))->resolve();

    expect($payload)->toHaveKeys(['owner', 'member_count', 'subscription_summary'])
        ->and($payload['subscription_summary'])->toMatchArray([
            'status' => 'trialing',
            'plan_name' => 'Starter',
        ])
        ->and($payload['member_count'])->toBe(0);
});

it('returns null subscription summary when freelancer has no subscription', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $freelancer->load('subscription');
    $freelancer->loadCount('memberships');

    $payload = (new FreelancerDetailResource($freelancer))->resolve();

    expect($payload['subscription_summary'])->toBeNull();
});

<?php

declare(strict_types=1);

use App\Features\Admin\Services\FreelancerOnboardingService;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    Plan::factory()->create([
        'name' => 'Starter',
        'slug' => DemoData::PLAN_STARTER_SLUG,
        'sort_order' => 1,
    ]);
});

it('creates freelancer owner membership and trialing subscription in one transaction', function () {
    $service = app(FreelancerOnboardingService::class);

    $freelancer = $service->onboard([
        'workspace_name' => 'Acme Studio',
        'owner_name' => 'Jane Owner',
        'owner_email' => 'jane@acme.test',
        'trial_days' => 21,
    ]);

    expect($freelancer->name)->toBe('Acme Studio')
        ->and($freelancer->status)->toBe(FreelancerStatus::Pending)
        ->and($freelancer->slug)->toBe('acme-studio');

    $owner = User::query()->where('email', 'jane@acme.test')->first();
    expect($owner)->not->toBeNull();

    expect(FreelancerMembership::query()
        ->where('freelancer_id', $freelancer->id)
        ->where('user_id', $owner->id)
        ->where('role', FreelancerMembershipRole::Owner)
        ->exists())->toBeTrue();

    $subscription = Subscription::query()->where('freelancer_id', $freelancer->id)->first();
    expect($subscription)->not->toBeNull()
        ->and($subscription->status->value)->toBe('trialing')
        ->and($subscription->trial_ends_at?->greaterThan(now()->addDays(20)))->toBeTrue();
});

it('uses custom plan when plan_id is provided', function () {
    $pro = Plan::factory()->create(['slug' => 'pro-plan', 'name' => 'Pro']);

    $freelancer = app(FreelancerOnboardingService::class)->onboard([
        'workspace_name' => 'Pro Studio',
        'owner_name' => 'Pro Owner',
        'owner_email' => 'pro@studio.test',
        'plan_id' => $pro->id,
    ]);

    expect(Subscription::query()->where('freelancer_id', $freelancer->id)->value('plan_id'))
        ->toBe($pro->id);
});

it('rolls back all records when onboarding fails', function () {
    $service = app(FreelancerOnboardingService::class);

    expect(fn () => $service->onboard([
        'workspace_name' => 'Rollback Studio',
        'owner_name' => 'Rollback Owner',
        'owner_email' => 'rollback@studio.test',
        'plan_id' => 99999,
    ]))->toThrow(ModelNotFoundException::class);

    expect(Freelancer::query()->where('name', 'Rollback Studio')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'rollback@studio.test')->exists())->toBeFalse();
});

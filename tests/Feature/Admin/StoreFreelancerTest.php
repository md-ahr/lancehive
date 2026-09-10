<?php

declare(strict_types=1);

use App\Features\Admin\Notifications\FreelancerInviteNotification;
use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Database\Seeders\Support\DemoData;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Plan::factory()->create([
        'name' => 'Starter',
        'slug' => DemoData::PLAN_STARTER_SLUG,
        'sort_order' => 1,
    ]);
});

it('creates freelancer workspace for super admin', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->postJson($this->apiUrl('admin/freelancers'), [
        'workspace_name' => 'New Studio',
        'owner_name' => 'New Owner',
        'owner_email' => 'owner@newstudio.test',
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'New Studio')
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('owner.email', 'owner@newstudio.test')
        ->assertJsonPath('subscription_summary.status', 'trialing');

    expect(Freelancer::query()->where('slug', 'new-studio')->exists())->toBeTrue()
        ->and(Subscription::query()->count())->toBe(1)
        ->and(FreelancerMembership::query()->count())->toBe(1);

    $owner = User::query()->where('email', 'owner@newstudio.test')->firstOrFail();
    expect($owner->role)->toBe(UserRole::User);
    Notification::assertSentTo($owner, FreelancerInviteNotification::class);
});

it('returns validation error when required fields are missing', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $this->postJson($this->apiUrl('admin/freelancers'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['workspace_name', 'owner_name', 'owner_email']);
});

it('returns validation error when owner email already exists', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());
    User::factory()->freelancer()->create(['email' => 'taken@studio.test']);

    $this->postJson($this->apiUrl('admin/freelancers'), [
        'workspace_name' => 'Taken Studio',
        'owner_name' => 'Taken Owner',
        'owner_email' => 'taken@studio.test',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['owner_email']);
});

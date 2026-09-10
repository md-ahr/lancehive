<?php

declare(strict_types=1);

use App\Core\Http\Exceptions\ApiException;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Services\PlanLimitService;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Facades\DB;

it('throws when the client limit is reached', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create(['max_clients' => 1]);
    Subscription::factory()->for($freelancer)->for($plan)->active()->create();

    DB::table('clients')->insert([
        'freelancer_id' => $freelancer->id,
        'name' => 'Existing Client',
        'status' => 'active',
        'contact_email' => 'client@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(PlanLimitService::class);

    expect(fn () => $service->assertCanAddClient($freelancer->id))
        ->toThrow(ApiException::class, 'Client limit reached for your plan.');
});

it('throws when the team member limit is reached', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create(['max_team_members' => 1]);
    Subscription::factory()->for($freelancer)->for($plan)->active()->create();

    DB::table('freelancer_memberships')->insert([
        'freelancer_id' => $freelancer->id,
        'user_id' => $freelancer->owner_user_id,
        'role' => 'owner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(PlanLimitService::class);

    expect(fn () => $service->assertCanAddTeamMember($freelancer->id))
        ->toThrow(ApiException::class, 'Team member limit reached for your plan.');
});

it('allows unlimited resources when the plan limit is null', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->custom()->create();
    Subscription::factory()->for($freelancer)->for($plan)->active()->create();

    app(PlanLimitService::class)->assertCanAddClient($freelancer->id);

    expect(true)->toBeTrue();
});

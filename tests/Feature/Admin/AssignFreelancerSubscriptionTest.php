<?php

declare(strict_types=1);

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\Tenancy\Models\Freelancer;
use Laravel\Sanctum\Sanctum;

it('assigns custom plan to freelancer', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->custom()->create();

    $this->patchJson($this->apiUrl('admin/freelancers/'.$freelancer->id.'/subscription'), [
        'plan_id' => $plan->id,
        'provider' => SubscriptionProvider::Manual->value,
    ])
        ->assertOk()
        ->assertJsonPath('plan_id', $plan->id)
        ->assertJsonPath('provider', 'manual')
        ->assertJsonPath('status', 'active');

    expect(AdminActivityLog::query()->where('action', 'freelancer.subscription.assign')->exists())->toBeTrue();
});

<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\Plan;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('treats null plan limits as unlimited', function () {
    $plan = Plan::factory()->create([
        'max_clients' => null,
        'max_projects' => null,
        'max_team_members' => null,
        'is_custom' => true,
    ]);

    expect($plan->max_clients)->toBeNull()
        ->and($plan->max_projects)->toBeNull()
        ->and($plan->max_team_members)->toBeNull()
        ->and($plan->is_custom)->toBeTrue();
});

it('relates to subscriptions', function () {
    $plan = Plan::factory()->create();

    expect($plan->subscriptions())->toBeInstanceOf(HasMany::class);
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

it('creates a client for workspace member', function () {
    $this->actingAsTenant()
        ->postJson($this->apiUrl('clients'), [
            'name' => 'BigCo Ltd',
            'contact_email' => 'billing@bigco.com',
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'BigCo Ltd')
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('contact_email', 'billing@bigco.com');

    expect(Client::query()->where('name', 'BigCo Ltd')->exists())->toBeTrue();
});

it('returns validation error when name is missing', function () {
    $this->actingAsTenant()
        ->postJson($this->apiUrl('clients'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('returns plan limit exceeded when client cap is reached', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 1]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();
    Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Over Limit Co'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'plan_limit_exceeded');
});

it('denies unauthenticated client creation', function () {
    $this->postJson($this->apiUrl('clients'), ['name' => 'BigCo Ltd'])
        ->assertUnauthorized();
});

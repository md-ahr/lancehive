<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

it('creates a project under a client for workspace member', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/projects"), [
            'name' => 'Website Redesign',
            'hourly_rate' => '1500.00',
            'deadline' => '2026-06-30',
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Website Redesign')
        ->assertJsonPath('client_id', $client->id)
        ->assertJsonPath('hourly_rate', '1500.00')
        ->assertJsonPath('currency', 'BDT')
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('deadline', '2026-06-30');

    expect(Project::query()->where('name', 'Website Redesign')->exists())->toBeTrue();
});

it('uses workspace default currency when currency is omitted', function () {
    $workspace = $this->createTenantWorkspace();
    $workspace['freelancer']->update(['default_currency' => 'USD']);
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/projects"), [
            'name' => 'USD Project',
            'hourly_rate' => '100.00',
        ])
        ->assertCreated()
        ->assertJsonPath('currency', 'USD');
});

it('returns validation error when hourly rate is missing', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/projects"), [
            'name' => 'Website Redesign',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hourly_rate']);
});

it('returns not found when creating a project for another tenant client', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/projects"), [
            'name' => 'Hijacked Project',
            'hourly_rate' => '100.00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns plan limit exceeded when project cap is reached', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_projects' => 1]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("clients/{$client->id}/projects"), [
            'name' => 'Over Limit Project',
            'hourly_rate' => '100.00',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'plan_limit_exceeded');
});

it('denies unauthenticated project creation', function () {
    $client = Client::factory()->create();

    $this->postJson($this->apiUrl("clients/{$client->id}/projects"), [
        'name' => 'Website Redesign',
        'hourly_rate' => '1500.00',
    ])
        ->assertUnauthorized();
});

<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

it('allows writes during valid trial', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->create([
        'trial_ends_at' => now()->addDays(7),
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Trial Client'])
        ->assertCreated();
});

it('allows reads when trial is expired', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->expiredTrial()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('clients'))
        ->assertOk();
});

it('blocks writes when trial is expired', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->expiredTrial()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Blocked Co'])
        ->assertForbidden()
        ->assertJsonPath('code', 'workspace_read_only');
});

it('allows writes for active subscription', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->active()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Allowed Co'])
        ->assertCreated();
});

it('allows writes when subscription is past due', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->pastDue()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Past Due Client'])
        ->assertCreated();
});

it('allows writes when canceled but still in billing period', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->canceledInPeriod()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Canceled Period Client'])
        ->assertCreated();
});

it('blocks writes when canceled after billing period ended', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->canceledPostPeriod()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Post Period Client'])
        ->assertForbidden()
        ->assertJsonPath('code', 'workspace_read_only');
});

it('allows super admin writes when workspace is read only', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 5]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->readOnly()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->withHeader('X-Freelancer-Id', (string) $workspace['freelancer']->id)
        ->postJson($this->apiUrl('clients'), ['name' => 'Admin Override Client'])
        ->assertCreated();
});

it('blocks invoice creation when read only but allows listing', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->for($workspace['freelancer'])->for($plan)->readOnly()->create();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($workspace['freelancer'])->for($client)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('projects/'.$project->id.'/client-invoices'))
        ->assertOk();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('projects/'.$project->id.'/client-invoices'), [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'workspace_read_only');
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;

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

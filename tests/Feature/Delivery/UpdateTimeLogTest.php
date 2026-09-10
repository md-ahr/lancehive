<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows a member to update their own time log', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create([
        'user_id' => $workspace['user']->id,
        'hours' => '2.00',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
            'hours' => '3.50',
            'description' => 'Updated description',
        ])
        ->assertOk()
        ->assertJsonPath('hours', '3.50')
        ->assertJsonPath('description', 'Updated description');
});

it('forbids a member from updating another users time log', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
            'hours' => '3.50',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('allows an admin to update another users time log', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Admin);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create(['hours' => '2.00']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
            'hours' => '4.00',
        ])
        ->assertOk()
        ->assertJsonPath('hours', '4.00');
});

it('returns validation error when updating a billed time log', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $invoiceItem = ClientInvoiceItem::factory()->create();
    $timeLog = TimeLog::factory()->for($task)->create([
        'user_id' => $workspace['user']->id,
        'client_invoice_item_id' => $invoiceItem->id,
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
            'hours' => '5.00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['time_log']);
});

it('returns not found when updating a time log in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->patchJson($this->apiUrl("time-logs/{$timeLog->id}"), [
            'hours' => '5.00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

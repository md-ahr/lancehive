<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('deletes the authenticated users own time log', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create([
        'user_id' => $workspace['user']->id,
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("time-logs/{$timeLog->id}"))
        ->assertOk()
        ->assertJsonPath('message', 'Time log deleted successfully.');

    expect(TimeLog::query()->find($timeLog->id))->toBeNull();
});

it('forbids a member from deleting another users time log', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("time-logs/{$timeLog->id}"))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');

    expect(TimeLog::query()->find($timeLog->id))->not->toBeNull();
});

it('returns validation error when deleting a billed time log', function () {
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
        ->deleteJson($this->apiUrl("time-logs/{$timeLog->id}"))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['time_log']);

    expect(TimeLog::query()->find($timeLog->id))->not->toBeNull();
});

it('returns not found when deleting a time log in another workspace', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $timeLog = TimeLog::factory()->for($task)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("time-logs/{$timeLog->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

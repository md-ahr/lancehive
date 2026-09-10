<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use Illuminate\Support\Facades\DB;

it('returns aggregated hours for a project via sql sum', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $otherProject = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $otherTask = Task::factory()->for($otherProject)->create();
    $billedItem = ClientInvoiceItem::factory()->create();

    TimeLog::factory()->for($task)->create(['hours' => '2.50', 'client_invoice_item_id' => null]);
    TimeLog::factory()->for($task)->create(['hours' => '1.50', 'client_invoice_item_id' => $billedItem->id]);
    TimeLog::factory()->for($otherTask)->create(['hours' => '10.00']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/time-summary"))
        ->assertOk()
        ->assertJsonPath('project_id', $project->id)
        ->assertJsonPath('total_hours', '4.00')
        ->assertJsonPath('billed_hours', '1.50')
        ->assertJsonPath('unbilled_hours', '2.50');
});

it('returns zero totals for a project with no time logs', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/time-summary"))
        ->assertOk()
        ->assertJsonPath('total_hours', '0.00')
        ->assertJsonPath('billed_hours', '0.00')
        ->assertJsonPath('unbilled_hours', '0.00');
});

it('returns not found for another tenants project', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/time-summary"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('uses a single aggregate query for the time summary', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    TimeLog::factory()->count(5)->for($task)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/time-summary"))
        ->assertOk();

    $aggregateQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'sum('))
        ->count();

    expect($aggregateQueries)->toBe(1);
});

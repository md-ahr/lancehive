<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('creates a draft invoice with bill to snapshot and invoice number', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create([
        'name' => 'BigCo Ltd',
        'contact_email' => 'billing@bigco.com',
    ]);
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/client-invoices"), [
            'due_date' => '2026-04-15',
            'notes' => 'Payment via bank transfer.',
            'tax_rate' => '10.00',
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'draft')
        ->assertJsonPath('project_id', $project->id)
        ->assertJsonPath('bill_to_name', 'BigCo Ltd')
        ->assertJsonPath('bill_to_email', 'billing@bigco.com')
        ->assertJsonPath('due_date', '2026-04-15')
        ->assertJsonPath('notes', 'Payment via bank transfer.')
        ->assertJsonPath('subtotal', '0.00')
        ->assertJsonPath('total', '0.00');

    $invoice = ClientInvoice::query()->first();
    expect($invoice?->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

it('prefills items from unbilled time logs when requested', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create([
        'hourly_rate' => '1500.00',
    ]);
    $task = Task::factory()->for($project)->create();
    TimeLog::factory()->for($task)->create([
        'user_id' => $workspace['user']->id,
        'hours' => '2.50',
        'description' => 'Design work',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/client-invoices"), [
            'prefill_unbilled_time' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('subtotal', '3750.00')
        ->assertJsonPath('total', '3750.00');

    expect(TimeLog::query()->whereNotNull('client_invoice_item_id')->count())->toBe(1);
});

it('creates an empty draft when no unbilled logs exist', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/client-invoices"), [
            'prefill_unbilled_time' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('subtotal', '0.00')
        ->assertJsonPath('total', '0.00');
});

it('returns not found when creating an invoice for another tenant project', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/client-invoices"), [])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('forbids members from creating invoices', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("projects/{$project->id}/client-invoices"), [])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

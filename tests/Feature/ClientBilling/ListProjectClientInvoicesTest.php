<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

it('returns paginated project invoices with cursor meta', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    ClientInvoice::factory()->count(3)->for($project)->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/client-invoices?per_page=2"))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'project_id',
                    'invoice_number',
                    'status',
                    'currency',
                    'subtotal',
                    'tax_rate',
                    'tax_amount',
                    'total',
                    'issued_at',
                    'due_date',
                    'sent_at',
                    'paid_at',
                    'notes',
                    'bill_to_name',
                    'bill_to_email',
                    'bill_to_address',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2);
});

it('filters project invoices by status', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    ClientInvoice::factory()->for($project)->for($workspace['freelancer'])->create();
    ClientInvoice::factory()->for($project)->for($workspace['freelancer'])->sent()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/client-invoices?status=sent"))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'sent');
});

it('returns not found when listing invoices for another tenant project', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$project->id}/client-invoices"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

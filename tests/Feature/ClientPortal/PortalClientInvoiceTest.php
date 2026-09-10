<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Project;

it('lists visible client invoices for the active client organization', function () {
    $portal = $this->createClientPortalUser();
    $project = Project::factory()->for($portal['client'])->for($portal['client']->freelancer)->create();
    $sentInvoice = ClientInvoice::factory()->for($project)->create([
        'status' => ClientInvoiceStatus::Sent,
    ]);
    ClientInvoice::factory()->for($project)->create([
        'status' => ClientInvoiceStatus::Draft,
    ]);

    $response = $this->actingAsClient($portal['user'], $portal['client'])
        ->getJson($this->apiUrl('portal/client-invoices'))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id'))
        ->toContain($sentInvoice->id)
        ->toHaveCount(1);
});

it('filters portal invoices by status', function () {
    $portal = $this->createClientPortalUser();
    $project = Project::factory()->for($portal['client'])->for($portal['client']->freelancer)->create();
    ClientInvoice::factory()->for($project)->create(['status' => ClientInvoiceStatus::Sent]);
    ClientInvoice::factory()->for($project)->create(['status' => ClientInvoiceStatus::Paid]);

    $this->actingAsClient($portal['user'], $portal['client'])
        ->getJson($this->apiUrl('portal/client-invoices?status=paid'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'paid');
});

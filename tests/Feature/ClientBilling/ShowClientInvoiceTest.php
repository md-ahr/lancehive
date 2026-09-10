<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('shows an invoice with items payments and outstanding balance', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create([
        'subtotal' => 1000,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1000,
    ]);
    ClientInvoiceItem::factory()->create([
        'client_invoice_id' => $invoice->id,
        'description' => 'Line item',
        'quantity' => 1,
        'rate' => 1000,
        'amount' => 1000,
    ]);
    ClientInvoicePayment::factory()->create([
        'client_invoice_id' => $invoice->id,
        'amount' => 250,
        'payment_method' => ClientPaymentMethod::Manual,
        'paid_at' => now(),
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertOk()
        ->assertJsonPath('id', $invoice->id)
        ->assertJsonCount(1, 'items')
        ->assertJsonCount(1, 'payments')
        ->assertJsonPath('outstanding_balance', '750.00')
        ->assertJsonPath('items.0.description', 'Line item');
});

it('allows members to view invoices read only', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertOk()
        ->assertJsonPath('id', $invoice->id);
});

it('returns not found when showing another tenant invoice', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('transitions draft to sent and sets issued and sent timestamps', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create([
        'due_date' => '2026-05-01',
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("client-invoices/{$invoice->id}"), [
            'status' => 'sent',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'sent')
        ->assertJsonPath('due_date', '2026-05-01');

    $invoice->refresh();

    expect($invoice->status)->toBe(ClientInvoiceStatus::Sent)
        ->and($invoice->issued_at)->not->toBeNull()
        ->and($invoice->sent_at)->not->toBeNull();
});

it('recalculates tax when tax rate is updated on a draft', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create([
        'tax_rate' => 0,
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
    ]);
    ClientInvoiceItem::factory()->create([
        'client_invoice_id' => $invoice->id,
        'quantity' => 1,
        'rate' => 1000,
        'amount' => 1000,
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("client-invoices/{$invoice->id}"), [
            'tax_rate' => '10.00',
        ])
        ->assertOk()
        ->assertJsonPath('subtotal', '1000.00')
        ->assertJsonPath('tax_amount', '100.00')
        ->assertJsonPath('total', '1100.00');
});

it('returns invoice not editable when updating a sent invoice', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->sent()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("client-invoices/{$invoice->id}"), [
            'notes' => 'Updated notes',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invoice_not_editable');
});

it('forbids members from updating invoices', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->patchJson($this->apiUrl("client-invoices/{$invoice->id}"), [
            'notes' => 'Blocked',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

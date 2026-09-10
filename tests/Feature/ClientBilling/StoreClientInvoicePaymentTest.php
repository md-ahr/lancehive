<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('records a partial payment without marking the invoice paid', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->sent()->create([
        'subtotal' => 1000,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1000,
        'paid_at' => null,
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/payments"), [
            'amount' => '400.00',
            'payment_method' => 'manual',
            'paid_at' => '2026-03-15T10:00:00+00:00',
        ])
        ->assertCreated()
        ->assertJsonPath('amount', '400.00')
        ->assertJsonPath('payment_method', 'manual');

    $invoice->refresh();

    expect($invoice->status)->toBe(ClientInvoiceStatus::Sent)
        ->and($invoice->paid_at)->toBeNull();
});

it('marks the invoice paid when payments cover the total', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->sent()->create([
        'subtotal' => 1000,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1000,
        'paid_at' => null,
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/payments"), [
            'amount' => '600.00',
            'payment_method' => ClientPaymentMethod::BankTransfer->value,
            'paid_at' => '2026-03-15T10:00:00+00:00',
        ])
        ->assertCreated();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/payments"), [
            'amount' => '400.00',
            'payment_method' => ClientPaymentMethod::Cash->value,
            'paid_at' => '2026-03-16T10:00:00+00:00',
        ])
        ->assertCreated();

    $invoice->refresh();

    expect($invoice->status)->toBe(ClientInvoiceStatus::Paid)
        ->and($invoice->paid_at)->not->toBeNull();
});

it('returns invoice not editable when recording payment on a draft invoice', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/payments"), [
            'amount' => '100.00',
            'payment_method' => 'manual',
            'paid_at' => '2026-03-15T10:00:00+00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invoice_not_editable');
});

it('forbids members from recording payments', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->sent()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/payments"), [
            'amount' => '100.00',
            'payment_method' => 'manual',
            'paid_at' => '2026-03-15T10:00:00+00:00',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

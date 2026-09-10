<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\ClientBilling\Models\ClientInvoicePayment;

it('generates sequential invoice numbers using workspace prefix', function () {
    $seed = ClientInvoice::factory()->create();
    test()->setTenantContext($seed->freelancer_id);
    $seed->freelancer->update(['invoice_number_prefix' => 'ACME']);

    $first = ClientInvoice::factory()->create([
        'freelancer_id' => $seed->freelancer_id,
        'project_id' => $seed->project_id,
        'invoice_number' => null,
    ]);

    $second = ClientInvoice::factory()->create([
        'freelancer_id' => $seed->freelancer_id,
        'project_id' => $seed->project_id,
        'invoice_number' => null,
    ]);

    expect($first->invoice_number)->toMatch('/^ACME-\d{4}-0001$/')
        ->and($second->invoice_number)->toMatch('/^ACME-\d{4}-0002$/');
});

it('generates sequential invoice numbers per freelancer', function () {
    $invoice = ClientInvoice::factory()->create(['invoice_number' => null]);
    test()->setTenantContext($invoice->freelancer_id);

    $second = ClientInvoice::factory()->create([
        'freelancer_id' => $invoice->freelancer_id,
        'project_id' => $invoice->project_id,
        'invoice_number' => null,
    ]);

    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-0001$/')
        ->and($second->invoice_number)->toMatch('/^INV-\d{4}-0002$/');
});

it('recalculates subtotal tax and total from items', function () {
    $invoice = ClientInvoice::factory()->create([
        'subtotal' => 0,
        'tax_rate' => 10,
        'tax_amount' => 0,
        'total' => 0,
    ]);
    test()->setTenantContext($invoice->freelancer_id);

    ClientInvoiceItem::factory()->create([
        'client_invoice_id' => $invoice->id,
        'quantity' => 2,
        'rate' => 1000,
        'amount' => 2000,
    ]);

    ClientInvoiceItem::factory()->create([
        'client_invoice_id' => $invoice->id,
        'quantity' => 1,
        'rate' => 500,
        'amount' => 500,
    ]);

    $invoice->refresh();

    expect($invoice->subtotal)->toBe('2500.00')
        ->and($invoice->tax_amount)->toBe('250.00')
        ->and($invoice->total)->toBe('2750.00');
});

it('sets paid_at when payments fully cover total', function () {
    $invoice = ClientInvoice::factory()->create([
        'subtotal' => 1000,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1000,
        'paid_at' => null,
    ]);
    test()->setTenantContext($invoice->freelancer_id);

    ClientInvoicePayment::factory()->create([
        'client_invoice_id' => $invoice->id,
        'amount' => 400,
        'payment_method' => ClientPaymentMethod::Manual,
        'paid_at' => now(),
    ]);

    $invoice->refresh();
    expect($invoice->paid_at)->toBeNull();

    ClientInvoicePayment::factory()->create([
        'client_invoice_id' => $invoice->id,
        'amount' => 600,
        'payment_method' => ClientPaymentMethod::Cash,
        'paid_at' => now(),
    ]);

    $invoice->refresh();
    expect($invoice->paid_at)->not->toBeNull();
});

it('clears paid_at when payments no longer cover total', function () {
    $invoice = ClientInvoice::factory()->paid()->create([
        'subtotal' => 1000,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1000,
    ]);
    test()->setTenantContext($invoice->freelancer_id);

    $payment = ClientInvoicePayment::factory()->create([
        'client_invoice_id' => $invoice->id,
        'amount' => 1000,
        'payment_method' => ClientPaymentMethod::Manual,
        'paid_at' => now(),
    ]);

    $invoice->refresh();
    expect($invoice->paid_at)->not->toBeNull();

    $payment->update(['amount' => 500]);

    $invoice->refresh();
    expect($invoice->paid_at)->toBeNull();
});

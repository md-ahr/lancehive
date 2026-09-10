<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('auto generates invoice number on create', function () {
    $invoice = ClientInvoice::factory()->create(['invoice_number' => null]);

    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

it('allows nullable bill_to_name', function () {
    $invoice = ClientInvoice::factory()->create(['bill_to_name' => null]);

    expect($invoice->bill_to_name)->toBeNull();
});

it('relates to freelancer project items and payments', function () {
    $invoice = ClientInvoice::factory()->create();
    test()->setTenantContext($invoice->freelancer_id);

    expect($invoice->freelancer)->not->toBeNull()
        ->and($invoice->project)->not->toBeNull()
        ->and($invoice->items())->toBeInstanceOf(HasMany::class)
        ->and($invoice->payments())->toBeInstanceOf(HasMany::class);
});

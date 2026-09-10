<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Http\Resources\ClientInvoiceResource;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use Illuminate\Http\Request;

it('exposes expected invoice list keys', function () {
    $invoice = ClientInvoice::factory()->create();
    test()->setTenantContext($invoice->freelancer_id);

    $payload = (new ClientInvoiceResource($invoice))->toArray(Request::create('/'));

    expect($payload)->toHaveKeys([
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
    ])->and($payload)->not->toHaveKey('outstanding_balance');
});

it('includes nested items payments and outstanding balance on detail shape', function () {
    $invoice = ClientInvoice::factory()->create(['total' => 1000]);
    test()->setTenantContext($invoice->freelancer_id);

    ClientInvoicePayment::factory()->create([
        'client_invoice_id' => $invoice->id,
        'amount' => 400,
        'payment_method' => ClientPaymentMethod::Manual,
        'paid_at' => now(),
    ]);

    $invoice->load(['items', 'payments']);

    $payload = (new ClientInvoiceResource($invoice))->toArray(Request::create('/'));

    expect($payload)->toHaveKeys(['items', 'payments', 'outstanding_balance'])
        ->and($payload['outstanding_balance'])->toBe('600.00');
});

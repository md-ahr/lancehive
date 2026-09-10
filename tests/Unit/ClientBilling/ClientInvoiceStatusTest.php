<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;

it('exposes all client invoice status values', function () {
    expect(ClientInvoiceStatus::values())->toBe(['draft', 'sent', 'paid', 'overdue', 'void']);
});

it('round trips invoice status through the model cast', function () {
    $invoice = ClientInvoice::factory()->sent()->create();

    expect($invoice->status)->toBe(ClientInvoiceStatus::Sent)
        ->and($invoice->getAttributes()['status'])->toBe('sent');
});

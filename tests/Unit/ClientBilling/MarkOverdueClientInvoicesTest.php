<?php

declare(strict_types=1);

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Jobs\MarkOverdueClientInvoicesJob;
use App\Features\ClientBilling\Models\ClientInvoice;

it('marks sent invoices past due date as overdue', function () {
    $overdue = ClientInvoice::factory()->sent()->create([
        'due_date' => now()->subDay()->toDateString(),
    ]);
    $current = ClientInvoice::factory()->sent()->create([
        'due_date' => now()->addDays(7)->toDateString(),
    ]);
    $paid = ClientInvoice::factory()->paid()->create([
        'due_date' => now()->subDays(3)->toDateString(),
    ]);
    $alreadyOverdue = ClientInvoice::factory()->create([
        'status' => ClientInvoiceStatus::Overdue,
        'due_date' => now()->subDays(5)->toDateString(),
        'issued_at' => now()->subDays(10)->toDateString(),
        'sent_at' => now()->subDays(10),
    ]);

    (new MarkOverdueClientInvoicesJob)->handle();

    expect($overdue->fresh()->status)->toBe(ClientInvoiceStatus::Overdue)
        ->and($current->fresh()->status)->toBe(ClientInvoiceStatus::Sent)
        ->and($paid->fresh()->status)->toBe(ClientInvoiceStatus::Paid)
        ->and($alreadyOverdue->fresh()->status)->toBe(ClientInvoiceStatus::Overdue);
});

it('is idempotent for invoices already marked overdue', function () {
    $invoice = ClientInvoice::factory()->create([
        'status' => ClientInvoiceStatus::Overdue,
        'due_date' => now()->subDays(2)->toDateString(),
        'issued_at' => now()->subDays(5)->toDateString(),
        'sent_at' => now()->subDays(5),
    ]);

    (new MarkOverdueClientInvoicesJob)->handle();

    expect($invoice->fresh()->status)->toBe(ClientInvoiceStatus::Overdue);
});

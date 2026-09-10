<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class MarkOverdueClientInvoices implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        ClientInvoice::withoutGlobalScopes()
            ->where('status', ClientInvoiceStatus::Sent)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => ClientInvoiceStatus::Overdue]);
    }
}

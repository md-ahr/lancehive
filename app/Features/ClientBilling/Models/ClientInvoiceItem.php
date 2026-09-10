<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Models;

use App\Features\ClientBilling\Services\ClientInvoiceService;
use App\Features\Delivery\Models\TimeLog;
use Database\Factories\ClientBilling\ClientInvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_invoice_id', 'description', 'quantity', 'rate', 'amount'])]
final class ClientInvoiceItem extends Model
{
    /** @use HasFactory<ClientInvoiceItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        $recalculate = function (ClientInvoiceItem $item): void {
            $invoice = $item->clientInvoice;

            if ($invoice !== null) {
                app(ClientInvoiceService::class)->recalculateTotals($invoice);
            }
        };

        self::saved($recalculate);
        self::deleted($recalculate);
    }

    protected static function newFactory(): ClientInvoiceItemFactory
    {
        return ClientInvoiceItemFactory::new();
    }

    public function clientInvoice(): BelongsTo
    {
        return $this->belongsTo(ClientInvoice::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }
}

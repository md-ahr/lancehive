<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Models;

use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use Database\Factories\ClientBilling\ClientInvoicePaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_invoice_id', 'amount', 'payment_method', 'reference', 'paid_at', 'notes'])]
final class ClientInvoicePayment extends Model
{
    /** @use HasFactory<ClientInvoicePaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => ClientPaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ClientInvoicePaymentFactory
    {
        return ClientInvoicePaymentFactory::new();
    }

    public function clientInvoice(): BelongsTo
    {
        return $this->belongsTo(ClientInvoice::class);
    }
}

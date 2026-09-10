<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Models;

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Models\Freelancer;
use Database\Factories\ClientBilling\ClientInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'freelancer_id',
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
])]
final class ClientInvoice extends Model
{
    /** @use HasFactory<ClientInvoiceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientInvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ClientInvoiceFactory
    {
        return ClientInvoiceFactory::new();
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientInvoicePayment::class);
    }
}

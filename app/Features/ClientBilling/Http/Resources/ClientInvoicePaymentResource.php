<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Resources;

use App\Features\ClientBilling\Models\ClientInvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClientInvoicePayment */
final class ClientInvoicePaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'payment_method' => $this->payment_method?->value,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

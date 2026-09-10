<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Resources;

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Services\ClientInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClientInvoice */
final class ClientInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status?->value,
            'currency' => $this->currency,
            'subtotal' => number_format((float) $this->subtotal, 2, '.', ''),
            'tax_rate' => $this->tax_rate !== null
                ? number_format((float) $this->tax_rate, 2, '.', '')
                : null,
            'tax_amount' => number_format((float) $this->tax_amount, 2, '.', ''),
            'total' => number_format((float) $this->total, 2, '.', ''),
            'issued_at' => $this->issued_at?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'sent_at' => $this->sent_at,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
            'bill_to_name' => $this->bill_to_name,
            'bill_to_email' => $this->bill_to_email,
            'bill_to_address' => $this->bill_to_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->relationLoaded('items')) {
            $data['items'] = ClientInvoiceItemResource::collection($this->items);
        }

        if ($this->relationLoaded('payments')) {
            $data['payments'] = ClientInvoicePaymentResource::collection($this->payments);
            $data['outstanding_balance'] = app(ClientInvoiceService::class)->outstandingBalance($this->resource);
        }

        return $data;
    }
}

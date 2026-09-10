<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Resources;

use App\Features\ClientBilling\Models\ClientInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClientInvoiceItem */
final class ClientInvoiceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'quantity' => number_format((float) $this->quantity, 2, '.', ''),
            'rate' => number_format((float) $this->rate, 2, '.', ''),
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

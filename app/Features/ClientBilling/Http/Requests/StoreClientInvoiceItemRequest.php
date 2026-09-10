<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Requests;

use App\Features\ClientBilling\Models\ClientInvoice;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $clientInvoice = $this->route('clientInvoice');

        return $clientInvoice instanceof ClientInvoice
            && ($this->user()?->can('update', $clientInvoice) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'decimal:0,2', 'gt:0'],
            'rate' => ['required', 'decimal:0,2', 'gte:0'],
        ];
    }
}

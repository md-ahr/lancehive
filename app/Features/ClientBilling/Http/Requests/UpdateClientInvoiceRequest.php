<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Requests;

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClientInvoiceRequest extends FormRequest
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
            'status' => ['sometimes', 'string', Rule::in([ClientInvoiceStatus::Sent->value])],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'tax_rate' => ['sometimes', 'nullable', 'decimal:0,2', 'gte:0'],
        ];
    }
}

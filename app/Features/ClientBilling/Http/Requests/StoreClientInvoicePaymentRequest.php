<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Requests;

use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Models\ClientInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientInvoicePaymentRequest extends FormRequest
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
            'amount' => ['required', 'decimal:0,2', 'gte:0.01'],
            'payment_method' => ['required', 'string', Rule::in([
                ClientPaymentMethod::Manual->value,
                ClientPaymentMethod::BankTransfer->value,
                ClientPaymentMethod::Cash->value,
                ClientPaymentMethod::Other->value,
            ])],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

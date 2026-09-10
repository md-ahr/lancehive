<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Requests;

use App\Features\ClientBilling\Models\ClientInvoice;
use Illuminate\Foundation\Http\FormRequest;

final class ShowClientInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $clientInvoice = $this->route('clientInvoice');

        return $clientInvoice instanceof ClientInvoice
            && ($this->user()?->can('view', $clientInvoice) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

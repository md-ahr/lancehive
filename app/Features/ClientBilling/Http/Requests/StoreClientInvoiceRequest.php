<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Requests;

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('create', [ClientInvoice::class, $project]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'due_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'tax_rate' => ['sometimes', 'nullable', 'decimal:0,2', 'gte:0'],
            'prefill_unbilled_time' => ['sometimes', 'boolean'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Requests;

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProjectClientInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('viewAny', ClientInvoice::class) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(ClientInvoiceStatus::values())],
        ];
    }
}

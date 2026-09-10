<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Requests;

use App\Features\Settings\Models\WorkspaceSettings;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkspaceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateAny', WorkspaceSettings::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_currency' => ['sometimes', 'string', 'size:3'],
            'invoice_number_prefix' => ['sometimes', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'default_tax_rate' => ['sometimes', 'nullable', 'decimal:0,2', 'gte:0', 'lte:100'],
            'invoice_footer_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'business_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'business_address' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('plans', 'slug')],
            'price_monthly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_yearly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'max_clients' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_projects' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_team_members' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_custom' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

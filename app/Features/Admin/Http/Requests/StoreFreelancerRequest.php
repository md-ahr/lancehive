<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFreelancerRequest extends FormRequest
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
            'workspace_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'plan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('plans', 'id')],
            'trial_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:90'],
        ];
    }
}

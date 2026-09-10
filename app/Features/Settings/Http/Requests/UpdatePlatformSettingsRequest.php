<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_trial_days' => ['sometimes', 'integer', 'min:1', 'max:90'],
            'default_plan_slug' => ['sometimes', 'string', Rule::exists('plans', 'slug')],
            'support_email' => ['sometimes', 'string', 'email', 'max:255'],
            'maintenance_mode' => ['sometimes', 'boolean'],
        ];
    }
}

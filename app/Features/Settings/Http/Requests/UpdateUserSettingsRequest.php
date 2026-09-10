<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Requests;

use App\Features\Settings\Rules\ValidIanaTimezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'string', 'max:64', new ValidIanaTimezone],
            'locale' => ['sometimes', 'string', Rule::in(['en'])],
            'notification_preferences' => ['sometimes', 'array'],
            'notification_preferences.subscription_alerts' => ['sometimes', 'boolean'],
            'notification_preferences.workspace_invites' => ['sometimes', 'boolean'],
            'notification_preferences.invoice_activity' => ['sometimes', 'boolean'],
        ];
    }
}

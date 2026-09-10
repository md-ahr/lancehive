<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Requests;

use App\Features\Settings\Models\WorkspaceSettings;
use Illuminate\Foundation\Http\FormRequest;

final class ShowWorkspaceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', WorkspaceSettings::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

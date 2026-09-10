<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Project::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(ProjectStatus::values())],
            'client_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}

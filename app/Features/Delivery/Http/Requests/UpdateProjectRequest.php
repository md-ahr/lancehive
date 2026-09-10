<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('update', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'hourly_rate' => ['sometimes', 'decimal:0,2', 'gte:0'],
            'status' => ['sometimes', 'string', Rule::in(ProjectStatus::values())],
            'deadline' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

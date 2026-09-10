<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($this->user()?->can('create', [Project::class, $client]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'hourly_rate' => ['required', 'decimal:0,2', 'gte:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'deadline' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(ProjectStatus::values())],
        ];
    }
}

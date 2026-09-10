<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Requests;

use App\Features\Tenancy\Enums\FreelancerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexFreelancerRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(FreelancerStatus::values())],
        ];
    }
}

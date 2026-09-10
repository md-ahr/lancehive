<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Requests;

use App\Features\Tenancy\Enums\FreelancerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFreelancerStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(FreelancerStatus::values())],
        ];
    }
}

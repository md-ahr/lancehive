<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Requests;

use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Foundation\Http\FormRequest;

final class IndexMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FreelancerMembership::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
        ];
    }
}

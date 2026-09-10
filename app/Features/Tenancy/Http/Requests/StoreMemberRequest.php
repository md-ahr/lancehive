<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Requests;

use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FreelancerMembership::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => [
                'required',
                'string',
                Rule::in([
                    FreelancerMembershipRole::Admin->value,
                    FreelancerMembershipRole::Member->value,
                ]),
            ],
        ];
    }
}

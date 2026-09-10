<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Requests;

use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Foundation\Http\FormRequest;

final class DestroyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $membership instanceof FreelancerMembership
            && ($this->user()?->can('delete', $membership) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

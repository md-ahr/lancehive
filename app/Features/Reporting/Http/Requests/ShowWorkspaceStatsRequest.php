<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;
use Illuminate\Foundation\Http\FormRequest;

final class ShowWorkspaceStatsRequest extends FormRequest
{
    use AuthorizesTenantMembership;

    public function authorize(): bool
    {
        return $this->canAccessTenant($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSavedReportRequest extends FormRequest
{
    use AuthorizesTenantMembership;

    public function authorize(): bool
    {
        if ($this->user()?->isSuperAdmin() ?? false) {
            return true;
        }

        return $this->canManageClientsAndProjects($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'filters' => ['sometimes', 'array'],
        ];
    }
}

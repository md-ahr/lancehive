<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Reporting\Enums\ReportType;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RunReportRequest extends FormRequest
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
        return [
            'report_type' => ['required', 'string', Rule::in(ReportType::workspaceRunnableValues())],
            'filters' => ['sometimes', 'array'],
            'filters.from' => ['sometimes', 'date_format:Y-m-d'],
            'filters.to' => ['sometimes', 'date_format:Y-m-d'],
            'filters.client_id' => ['sometimes', 'integer'],
            'filters.project_id' => ['sometimes', 'integer'],
            'filters.user_id' => ['sometimes', 'integer'],
            'filters.status' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
        ];
    }
}

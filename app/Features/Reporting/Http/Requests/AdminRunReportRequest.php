<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Reporting\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminRunReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(ReportType::platformRunnableValues())],
            'filters' => ['sometimes', 'array'],
            'filters.from' => ['sometimes', 'date_format:Y-m-d'],
            'filters.to' => ['sometimes', 'date_format:Y-m-d'],
            'filters.status' => ['sometimes', 'string'],
            'filters.plan_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
        ];
    }
}

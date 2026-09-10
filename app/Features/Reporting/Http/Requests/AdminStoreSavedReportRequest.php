<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\SavedReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminStoreSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SavedReport::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'report_type' => ['required', 'string', Rule::in(ReportType::platformRunnableValues())],
            'filters' => ['sometimes', 'array'],
        ];
    }
}

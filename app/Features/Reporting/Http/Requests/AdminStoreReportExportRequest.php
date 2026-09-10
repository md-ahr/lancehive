<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\ReportExport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminStoreReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReportExport::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(ReportType::platformRunnableValues())],
            'filters' => ['sometimes', 'array'],
            'format' => ['required', 'string', Rule::in(ExportFormat::supportedValues())],
            'saved_report_id' => ['sometimes', 'integer'],
        ];
    }
}

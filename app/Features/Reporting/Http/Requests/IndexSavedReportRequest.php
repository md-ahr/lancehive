<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use App\Features\Reporting\Models\SavedReport;
use Illuminate\Foundation\Http\FormRequest;

final class IndexSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SavedReport::class) ?? false;
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

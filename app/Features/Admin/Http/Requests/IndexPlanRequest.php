<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IndexPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

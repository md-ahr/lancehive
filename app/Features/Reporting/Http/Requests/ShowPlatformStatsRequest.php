<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShowPlatformStatsRequest extends FormRequest
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
        return [];
    }
}

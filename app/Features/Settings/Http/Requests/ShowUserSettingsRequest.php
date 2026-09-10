<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShowUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

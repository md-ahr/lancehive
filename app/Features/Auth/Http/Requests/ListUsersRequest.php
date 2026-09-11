<?php

declare(strict_types=1);

namespace App\Features\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('super-admin') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
        ];
    }
}

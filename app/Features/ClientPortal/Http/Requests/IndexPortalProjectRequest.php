<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Requests;

use App\Features\ClientPortal\Models\ClientMembership;
use Illuminate\Foundation\Http\FormRequest;

final class IndexPortalProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ClientMembership::class) ?? false;
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

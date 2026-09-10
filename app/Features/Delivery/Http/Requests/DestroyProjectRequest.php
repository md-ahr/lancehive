<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class DestroyProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('delete', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

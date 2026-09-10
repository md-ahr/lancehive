<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Enums\TaskStatus;
use App\Features\Delivery\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('update', $task) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(TaskStatus::values())],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'estimated_hours' => ['sometimes', 'nullable', 'decimal:0,2', 'gte:0'],
        ];
    }
}

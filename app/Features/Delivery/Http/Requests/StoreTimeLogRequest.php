<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('create', [TimeLog::class, $task]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'decimal:0,2', 'min:0.01', 'max:24'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'logged_at' => ['required', 'date'],
        ];
    }
}

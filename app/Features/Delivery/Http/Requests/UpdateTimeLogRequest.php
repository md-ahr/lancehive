<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Models\TimeLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $timeLog = $this->route('timeLog');

        return $timeLog instanceof TimeLog
            && ($this->user()?->can('update', $timeLog) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hours' => ['sometimes', 'decimal:0,2', 'min:0.01', 'max:24'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'logged_at' => ['sometimes', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $timeLog = $this->route('timeLog');

            if ($timeLog instanceof TimeLog && $timeLog->client_invoice_item_id !== null) {
                $validator->errors()->add('time_log', 'Billed time log cannot be edited.');
            }
        });
    }
}

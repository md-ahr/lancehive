<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Requests;

use App\Features\Delivery\Models\TimeLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class DestroyTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $timeLog = $this->route('timeLog');

        return $timeLog instanceof TimeLog
            && ($this->user()?->can('delete', $timeLog) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $timeLog = $this->route('timeLog');

            if ($timeLog instanceof TimeLog && $timeLog->client_invoice_item_id !== null) {
                $validator->errors()->add('time_log', 'Billed time log cannot be deleted.');
            }
        });
    }
}

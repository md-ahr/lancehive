<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Requests;

use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignFreelancerSubscriptionRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'provider' => ['sometimes', 'string', Rule::in(SubscriptionProvider::values())],
        ];
    }
}

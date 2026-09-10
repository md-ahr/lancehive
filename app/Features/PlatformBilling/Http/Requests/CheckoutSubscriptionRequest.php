<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Requests;

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Http\Concerns\AuthorizesFreelancerOwner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckoutSubscriptionRequest extends FormRequest
{
    use AuthorizesFreelancerOwner;

    public function authorize(): bool
    {
        return $this->userIsFreelancerOwner();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(
                    fn ($query) => $query->where('is_custom', false)->where('is_active', true),
                ),
            ],
            'billing_interval' => ['required', 'string', Rule::in(BillingInterval::values())],
        ];
    }
}

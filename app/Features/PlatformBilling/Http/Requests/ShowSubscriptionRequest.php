<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Requests;

use App\Features\PlatformBilling\Http\Concerns\AuthorizesFreelancerOwner;
use Illuminate\Foundation\Http\FormRequest;

final class ShowSubscriptionRequest extends FormRequest
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
        return [];
    }
}

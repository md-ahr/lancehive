<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Resources;

use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
final class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'freelancer_id' => $this->freelancer_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status?->value,
            'billing_interval' => $this->billing_interval?->value,
            'trial_ends_at' => $this->trial_ends_at,
            'current_period_start' => $this->current_period_start,
            'current_period_end' => $this->current_period_end,
            'read_only_at' => $this->read_only_at,
            'canceled_at' => $this->canceled_at,
            'provider' => $this->provider?->value,
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Resources;

use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
final class SubscriptionSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status?->value,
            'plan_name' => $this->plan?->name,
            'read_only' => ! $this->canWrite(),
            'trial_ends_at' => $this->trial_ends_at,
        ];
    }
}

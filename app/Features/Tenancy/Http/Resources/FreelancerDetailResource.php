<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Resources;

use App\Features\Auth\Http\Resources\UserResource;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Freelancer */
final class FreelancerDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status?->value,
            'owner_user_id' => $this->owner_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'owner' => $this->whenLoaded('owner', fn () => $this->owner !== null
                ? new UserResource($this->owner)
                : null),
            'member_count' => $this->when(
                isset($this->memberships_count),
                fn () => $this->memberships_count,
            ),
            'subscription_summary' => $this->whenLoaded('subscription', function () {
                if ($this->subscription === null) {
                    return null;
                }

                return [
                    'status' => $this->subscription->status?->value,
                    'plan_name' => $this->subscription->plan?->name,
                    'trial_ends_at' => $this->subscription->trial_ends_at,
                ];
            }),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Resources;

use App\Features\Auth\Http\Resources\UserResource;
use App\Features\PlatformBilling\Http\Resources\SubscriptionSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'memberships' => FreelancerMembershipResource::collection($this->resource['memberships']),
            'active_freelancer' => $this->resource['active_freelancer'] !== null
                ? new FreelancerResource($this->resource['active_freelancer'])
                : null,
            'subscription' => $this->resource['subscription'] !== null
                ? new SubscriptionSummaryResource($this->resource['subscription'])
                : null,
        ];
    }
}

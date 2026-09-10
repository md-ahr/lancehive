<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Resources;

use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FreelancerMembership */
final class FreelancerMembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'freelancer_id' => $this->freelancer_id,
            'user_id' => $this->user_id,
            'role' => $this->role?->value,
            'freelancer' => $this->whenLoaded('freelancer', fn () => $this->freelancer !== null
                ? new FreelancerResource($this->freelancer)
                : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

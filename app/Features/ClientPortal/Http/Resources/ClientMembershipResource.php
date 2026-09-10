<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Resources;

use App\Features\Auth\Http\Resources\UserResource;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClientMembership */
final class ClientMembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'user_id' => $this->user_id,
            'role' => $this->role?->value,
            'user' => $this->whenLoaded('user', fn () => $this->user !== null
                ? new UserResource($this->user)
                : null),
            'client' => $this->whenLoaded('client', fn () => $this->client !== null
                ? new ClientResource($this->client)
                : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

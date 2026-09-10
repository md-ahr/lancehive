<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Resources;

use App\Features\Delivery\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
final class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'name' => $this->name,
            'hourly_rate' => number_format((float) $this->hourly_rate, 2, '.', ''),
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'deadline' => $this->deadline?->toDateString(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

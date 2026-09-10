<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Resources;

use App\Features\Delivery\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
final class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'status' => $this->status?->value,
            'due_date' => $this->due_date?->toDateString(),
            'estimated_hours' => $this->estimated_hours !== null
                ? number_format((float) $this->estimated_hours, 2, '.', '')
                : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

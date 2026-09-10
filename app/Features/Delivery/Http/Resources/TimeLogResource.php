<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Resources;

use App\Features\Delivery\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeLog */
final class TimeLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'hours' => number_format((float) $this->hours, 2, '.', ''),
            'description' => $this->description,
            'logged_at' => $this->logged_at,
            'client_invoice_item_id' => $this->client_invoice_item_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $project_id
 * @property string $total_hours
 * @property string $billed_hours
 * @property string $unbilled_hours
 */
final class ProjectTimeSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{project_id: int, total_hours: string, billed_hours: string, unbilled_hours: string} $data */
        $data = $this->resource;

        return [
            'project_id' => $data['project_id'],
            'total_hours' => $data['total_hours'],
            'billed_hours' => $data['billed_hours'],
            'unbilled_hours' => $data['unbilled_hours'],
        ];
    }
}

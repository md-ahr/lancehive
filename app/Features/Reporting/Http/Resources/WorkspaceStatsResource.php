<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $active_clients
 * @property int $active_projects
 * @property string $hours_this_month
 * @property string $unbilled_hours
 * @property string $outstanding_invoice_total
 */
final class WorkspaceStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{active_clients: int, active_projects: int, hours_this_month: string, unbilled_hours: string, outstanding_invoice_total: string} $data */
        $data = $this->resource;

        return [
            'active_clients' => $data['active_clients'],
            'active_projects' => $data['active_projects'],
            'hours_this_month' => $data['hours_this_month'],
            'unbilled_hours' => $data['unbilled_hours'],
            'outstanding_invoice_total' => $data['outstanding_invoice_total'],
        ];
    }
}

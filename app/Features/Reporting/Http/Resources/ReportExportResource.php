<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Resources;

use App\Features\Reporting\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportExport */
final class ReportExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_type' => $this->report_type?->value,
            'format' => $this->format?->value,
            'status' => $this->status?->value,
            'row_count' => $this->row_count,
            'download_url' => $this->resource->getAttribute('download_url'),
            'expires_at' => $this->expires_at,
            'completed_at' => $this->completed_at,
            'error_message' => $this->error_message,
        ];
    }
}

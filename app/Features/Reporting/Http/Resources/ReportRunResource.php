<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $report_type
 * @property array<string, mixed> $filters
 * @property array<string, mixed> $summary
 * @property array<string, mixed> $preview
 */
final class ReportRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{report_type: string, filters: array<string, mixed>, summary: array<string, mixed>, preview: array<string, mixed>} $data */
        $data = $this->resource;

        return [
            'report_type' => $data['report_type'],
            'filters' => $data['filters'],
            'summary' => $data['summary'],
            'preview' => $data['preview'],
        ];
    }
}

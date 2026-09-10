<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, int> $freelancers_by_status
 * @property list<array{plan_id: int, plan_name: string, count: int}> $subscriptions_by_plan
 * @property array<string, int> $subscriptions_by_status
 * @property int $trials_ending_soon
 */
final class PlatformStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{freelancers_by_status: array<string, int>, subscriptions_by_plan: list<array{plan_id: int, plan_name: string, count: int}>, subscriptions_by_status: array<string, int>, trials_ending_soon: int} $data */
        $data = $this->resource;

        return [
            'freelancers_by_status' => $data['freelancers_by_status'],
            'subscriptions_by_plan' => $data['subscriptions_by_plan'],
            'subscriptions_by_status' => $data['subscriptions_by_status'],
            'trials_ending_soon' => $data['trials_ending_soon'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Resources;

use App\Features\PlatformBilling\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
final class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price_monthly' => $this->price_monthly !== null ? number_format((float) $this->price_monthly, 2, '.', '') : null,
            'price_yearly' => $this->price_yearly !== null ? number_format((float) $this->price_yearly, 2, '.', '') : null,
            'currency' => $this->currency,
            'max_clients' => $this->max_clients,
            'max_projects' => $this->max_projects,
            'max_team_members' => $this->max_team_members,
            'is_custom' => $this->is_custom,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

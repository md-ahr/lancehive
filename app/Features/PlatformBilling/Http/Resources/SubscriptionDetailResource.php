<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Resources;

use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/** @mixin Subscription */
final class SubscriptionDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $freelancerId = $this->freelancer_id;
        $plan = $this->plan;

        return [
            ...(new SubscriptionResource($this->resource))->resolve($request),
            'days_remaining' => $this->daysRemaining(),
            'usage' => [
                'clients' => DB::table('clients')->where('freelancer_id', $freelancerId)->count(),
                'projects' => DB::table('projects')->where('freelancer_id', $freelancerId)->whereNull('deleted_at')->count(),
                'team_members' => DB::table('freelancer_memberships')->where('freelancer_id', $freelancerId)->count(),
                'max_clients' => $plan?->max_clients,
                'max_projects' => $plan?->max_projects,
                'max_team_members' => $plan?->max_team_members,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use Illuminate\Support\Facades\DB;

final class PlanLimitService
{
    public function __construct(private SubscriptionCache $subscriptionCache) {}

    public function assertCanAddClient(int $freelancerId): void
    {
        $limit = $this->subscriptionCache->forFreelancer($freelancerId)?->plan?->max_clients;

        if ($limit === null) {
            return;
        }

        $count = DB::table('clients')
            ->where('freelancer_id', $freelancerId)
            ->count();

        if ($count >= $limit) {
            throw new ApiException(ApiErrorCode::PlanLimitExceeded, 'Client limit reached for your plan.');
        }
    }

    public function assertCanAddProject(int $freelancerId): void
    {
        $limit = $this->subscriptionCache->forFreelancer($freelancerId)?->plan?->max_projects;

        if ($limit === null) {
            return;
        }

        $count = DB::table('projects')
            ->where('freelancer_id', $freelancerId)
            ->whereNull('deleted_at')
            ->count();

        if ($count >= $limit) {
            throw new ApiException(ApiErrorCode::PlanLimitExceeded, 'Project limit reached for your plan.');
        }
    }

    public function assertCanAddTeamMember(int $freelancerId): void
    {
        $limit = $this->subscriptionCache->forFreelancer($freelancerId)?->plan?->max_team_members;

        if ($limit === null) {
            return;
        }

        $count = DB::table('freelancer_memberships')
            ->where('freelancer_id', $freelancerId)
            ->count();

        if ($count >= $limit) {
            throw new ApiException(ApiErrorCode::PlanLimitExceeded, 'Team member limit reached for your plan.');
        }
    }
}

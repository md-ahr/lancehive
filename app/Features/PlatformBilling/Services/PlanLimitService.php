<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PlanLimitService
{
    public function assertCanAddClient(int $freelancerId): void
    {
        $this->assertWithinLimit($freelancerId, 'clients', 'max_clients', 'Client limit reached for your plan.');
    }

    public function assertCanAddProject(int $freelancerId): void
    {
        $this->assertWithinLimit(
            $freelancerId,
            'projects',
            'max_projects',
            'Project limit reached for your plan.',
            static fn ($query) => $query->whereNull('deleted_at'),
        );
    }

    public function assertCanAddTeamMember(int $freelancerId): void
    {
        $this->assertWithinLimit(
            $freelancerId,
            'freelancer_memberships',
            'max_team_members',
            'Team member limit reached for your plan.',
        );
    }

    /**
     * @param  (callable(Builder): Builder)|null  $constraint
     */
    private function assertWithinLimit(
        int $freelancerId,
        string $table,
        string $limitColumn,
        string $message,
        ?callable $constraint = null,
    ): void {
        DB::transaction(function () use ($freelancerId, $table, $limitColumn, $message, $constraint): void {
            $subscription = Subscription::query()
                ->with('plan')
                ->where('freelancer_id', $freelancerId)
                ->lockForUpdate()
                ->first();

            $limit = $subscription?->plan?->{$limitColumn};

            if ($limit === null) {
                return;
            }

            $query = DB::table($table)->where('freelancer_id', $freelancerId);

            if ($constraint !== null) {
                $query = $constraint($query);
            }

            if ($query->count() >= $limit) {
                throw new ApiException(ApiErrorCode::PlanLimitExceeded, $message);
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Concerns;

use App\Core\Tenancy\TenantContext;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;

trait AuthorizesFreelancerOwner
{
    protected function userIsFreelancerOwner(): bool
    {
        $freelancerId = app(TenantContext::class)->freelancerId();
        $user = $this->user();

        if ($freelancerId === null || $user === null) {
            return false;
        }

        return FreelancerMembership::query()
            ->where('freelancer_id', $freelancerId)
            ->where('user_id', $user->id)
            ->where('role', FreelancerMembershipRole::Owner)
            ->exists();
    }
}

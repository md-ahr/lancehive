<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Policies\Concerns;

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\FreelancerMembership;

trait AuthorizesTenantMembership
{
    protected function membershipFor(User $user): ?FreelancerMembership
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        if ($freelancerId === null) {
            return null;
        }

        return $user->freelancerMemberships()
            ->where('freelancer_id', $freelancerId)
            ->first();
    }

    protected function isMember(User $user): bool
    {
        return $this->membershipFor($user) !== null;
    }
}

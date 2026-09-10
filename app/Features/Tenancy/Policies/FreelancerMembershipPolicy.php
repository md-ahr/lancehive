<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Policies;

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class FreelancerMembershipPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user): bool
    {
        return $this->membershipFor($user)?->role->canManageTeam() ?? false;
    }

    public function delete(User $user, FreelancerMembership $membership): bool
    {
        $actorMembership = $this->membershipFor($user);

        if ($actorMembership === null) {
            return false;
        }

        if ($membership->role === FreelancerMembershipRole::Owner) {
            return false;
        }

        return $actorMembership->role->canRemoveMember($membership->role);
    }
}

<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Policies;

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Policies\Concerns\AuthorizesClientMembership as AuthorizesClientMembershipConcern;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class ClientMembershipPolicy
{
    use AuthorizesClientMembershipConcern;
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->isClientMember($user);
    }

    public function create(User $user, Client $client): bool
    {
        return $this->membershipFor($user)?->role->canManageClientsAndProjects() ?? false;
    }
}

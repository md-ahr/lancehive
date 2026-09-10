<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Policies\Concerns;

use App\Core\ClientPortal\ClientContext;
use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Models\ClientMembership;

trait AuthorizesClientMembership
{
    protected function clientMembershipFor(User $user): ?ClientMembership
    {
        $clientId = app(ClientContext::class)->clientId();

        if ($clientId === null) {
            return null;
        }

        return $user->clientMemberships()
            ->where('client_id', $clientId)
            ->first();
    }

    protected function isClientMember(User $user): bool
    {
        return $this->clientMembershipFor($user) !== null;
    }
}

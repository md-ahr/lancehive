<?php

declare(strict_types=1);

namespace App\Features\Delivery\Policies;

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class ClientPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user): bool
    {
        return $this->isMember($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->isMember($user);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->isMember($user);
    }
}

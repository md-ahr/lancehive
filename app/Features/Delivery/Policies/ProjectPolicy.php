<?php

declare(strict_types=1);

namespace App\Features\Delivery\Policies;

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class ProjectPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->canAccessTenant($user);
    }

    public function create(User $user, ?Client $client = null): bool
    {
        if (! $this->canManageClientsAndProjects($user)) {
            return false;
        }

        if ($client === null) {
            return true;
        }

        return $this->clientBelongsToTenant($client);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->canManageClientsAndProjects($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->canManageClientsAndProjects($user);
    }

    private function clientBelongsToTenant(Client $client): bool
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        return $freelancerId !== null && $client->freelancer_id === $freelancerId;
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Settings\Policies;

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class WorkspaceSettingsPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function updateAny(User $user): bool
    {
        return $this->canManageClientsAndProjects($user);
    }
}

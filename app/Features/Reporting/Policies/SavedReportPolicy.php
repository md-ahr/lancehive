<?php

declare(strict_types=1);

namespace App\Features\Reporting\Policies;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class SavedReportPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user) || $user->isSuperAdmin();
    }

    public function view(User $user, SavedReport $savedReport): bool
    {
        if ($savedReport->isPlatformScoped()) {
            return $user->isSuperAdmin();
        }

        return $this->canAccessTenant($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageClientsAndProjects($user) || $user->isSuperAdmin();
    }

    public function update(User $user, SavedReport $savedReport): bool
    {
        if ($savedReport->isPlatformScoped()) {
            return $user->isSuperAdmin();
        }

        return $this->canManageClientsAndProjects($user);
    }

    public function delete(User $user, SavedReport $savedReport): bool
    {
        return $this->update($user, $savedReport);
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Reporting\Policies;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class ReportExportPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user) || $user->isSuperAdmin();
    }

    public function view(User $user, ReportExport $reportExport): bool
    {
        if ($reportExport->isPlatformScoped()) {
            return $user->isSuperAdmin() && $reportExport->requested_by_user_id === $user->id;
        }

        return $this->canAccessTenant($user)
            && $reportExport->requested_by_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->canManageClientsAndProjects($user) || $user->isSuperAdmin();
    }
}

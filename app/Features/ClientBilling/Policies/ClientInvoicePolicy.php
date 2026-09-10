<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Policies;

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class ClientInvoicePolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ClientInvoice $clientInvoice): bool
    {
        return $this->canAccessTenant($user);
    }

    public function create(User $user, ?Project $project = null): bool
    {
        if (! $this->canManageInvoices($user)) {
            return false;
        }

        if ($project === null) {
            return true;
        }

        $freelancerId = app(TenantContext::class)->freelancerId();

        return $freelancerId !== null && $project->freelancer_id === $freelancerId;
    }

    public function update(User $user, ClientInvoice $clientInvoice): bool
    {
        return $this->canManageInvoices($user);
    }

    public function delete(User $user, ClientInvoice $clientInvoice): bool
    {
        return $this->canManageInvoices($user);
    }
}

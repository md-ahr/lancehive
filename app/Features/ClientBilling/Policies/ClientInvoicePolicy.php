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
        return $this->isMember($user);
    }

    public function view(User $user, ClientInvoice $clientInvoice): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user, ?Project $project = null): bool
    {
        $membership = $this->membershipFor($user);

        if (! ($membership?->role->canManageInvoices() ?? false)) {
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
        $membership = $this->membershipFor($user);

        return $membership?->role->canManageInvoices() ?? false;
    }

    public function delete(User $user, ClientInvoice $clientInvoice): bool
    {
        $membership = $this->membershipFor($user);

        return $membership?->role->canManageInvoices() ?? false;
    }
}

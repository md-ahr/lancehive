<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Policies;

use App\Features\Auth\Models\User;
use App\Features\ClientBilling\Models\ClientInvoice;
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

    public function create(User $user): bool
    {
        $membership = $this->membershipFor($user);

        return $membership?->role->canManageInvoices() ?? false;
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

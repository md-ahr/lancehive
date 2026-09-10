<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Policies;

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class FreelancerPolicy
{
    use AuthorizesTenantMembership;

    public function view(User $user, Freelancer $freelancer): bool
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        return $freelancerId === $freelancer->id && $this->isMember($user);
    }
}

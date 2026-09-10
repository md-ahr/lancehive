<?php

declare(strict_types=1);

namespace App\Features\Delivery\Policies;

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class TimeLogPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function view(User $user, TimeLog $timeLog): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user, ?Task $task = null): bool
    {
        return $this->isMember($user);
    }

    public function update(User $user, TimeLog $timeLog): bool
    {
        $membership = $this->membershipFor($user);

        if ($membership === null) {
            return false;
        }

        if ($membership->role->canManageAllTimeLogs()) {
            return true;
        }

        return $timeLog->user_id === $user->id;
    }

    public function delete(User $user, TimeLog $timeLog): bool
    {
        return $this->update($user, $timeLog);
    }
}

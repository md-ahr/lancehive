<?php

declare(strict_types=1);

namespace App\Features\Delivery\Policies;

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Tenancy\Policies\Concerns\AuthorizesTenantMembership;

final class TaskPolicy
{
    use AuthorizesTenantMembership;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user, ?Project $project = null): bool
    {
        if (! $this->isMember($user)) {
            return false;
        }

        if ($project === null) {
            return true;
        }

        return $this->projectBelongsToTenant($project);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->isMember($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->isMember($user);
    }

    private function projectBelongsToTenant(Project $project): bool
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        return $freelancerId !== null && $project->freelancer_id === $freelancerId;
    }
}

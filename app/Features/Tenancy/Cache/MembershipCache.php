<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Cache;

use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class MembershipCache
{
    private const int TTL_SECONDS = 300;

    /**
     * @return Collection<int, FreelancerMembership>
     */
    public function forUser(int $userId): Collection
    {
        /** @var Collection<int, FreelancerMembership> $memberships */
        $memberships = Cache::remember(
            $this->key($userId),
            self::TTL_SECONDS,
            fn (): Collection => FreelancerMembership::query()
                ->with('freelancer')
                ->where('user_id', $userId)
                ->get(),
        );

        return $memberships;
    }

    public function forget(int $userId): void
    {
        Cache::forget($this->key($userId));
    }

    private function key(int $userId): string
    {
        return "memberships:user:{$userId}";
    }
}

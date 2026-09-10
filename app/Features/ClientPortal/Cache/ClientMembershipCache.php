<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Cache;

use App\Features\ClientPortal\Models\ClientMembership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class ClientMembershipCache
{
    private const int TTL_SECONDS = 300;

    /**
     * @return Collection<int, ClientMembership>
     */
    public function forUser(int $userId): Collection
    {
        /** @var Collection<int, ClientMembership> $memberships */
        $memberships = Cache::remember(
            $this->key($userId),
            self::TTL_SECONDS,
            fn (): Collection => ClientMembership::query()
                ->with('client')
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
        return "client-memberships:user:{$userId}";
    }
}

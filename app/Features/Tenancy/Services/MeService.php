<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Cache\MembershipCache;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Database\Eloquent\Collection;

final class MeService
{
    public function __construct(
        private readonly MembershipCache $membershipCache,
        private readonly SubscriptionCache $subscriptionCache,
    ) {}

    /**
     * @return array{
     *     user: User,
     *     memberships: Collection<int, FreelancerMembership>,
     *     active_freelancer: ?Freelancer,
     *     subscription: ?Subscription
     * }
     */
    public function forUser(User $user, ?int $requestedFreelancerId): array
    {
        $memberships = $this->membershipCache->forUser($user->id);
        $activeFreelancerId = $this->resolveActiveFreelancerId($memberships, $requestedFreelancerId);

        $activeFreelancer = null;
        $subscription = null;

        if ($activeFreelancerId !== null) {
            $activeFreelancer = $memberships
                ->firstWhere('freelancer_id', $activeFreelancerId)
                ?->freelancer;

            $subscription = $this->subscriptionCache->forFreelancer($activeFreelancerId);
        }

        return [
            'user' => $user,
            'memberships' => $memberships,
            'active_freelancer' => $activeFreelancer,
            'subscription' => $subscription,
        ];
    }

    /**
     * @param  Collection<int, FreelancerMembership>  $memberships
     */
    private function resolveActiveFreelancerId(Collection $memberships, ?int $requestedFreelancerId): ?int
    {
        if ($requestedFreelancerId !== null) {
            if (! $memberships->contains('freelancer_id', $requestedFreelancerId)) {
                throw new ApiException(ApiErrorCode::Forbidden);
            }

            return $requestedFreelancerId;
        }

        if ($memberships->count() === 1) {
            return (int) $memberships->first()->freelancer_id;
        }

        return null;
    }
}

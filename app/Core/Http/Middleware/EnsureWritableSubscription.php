<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWritableSubscription
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SubscriptionCache $subscriptionCache,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isSuperAdmin()) {
            return $next($request);
        }

        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $subscription = $this->subscriptionCache->forFreelancer($freelancerId);

        if ($subscription !== null && ! $subscription->allowsWrites()) {
            throw new ApiException(ApiErrorCode::WorkspaceReadOnly);
        }

        return $next($request);
    }
}

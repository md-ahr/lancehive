<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFreelancerContext
{
    public function __construct(private TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new ApiException(ApiErrorCode::Unauthenticated);
        }

        $freelancerId = $this->resolveFreelancerId($request, $user);

        if ($freelancerId === null) {
            throw new ApiException(
                ApiErrorCode::Forbidden,
                'Active freelancer workspace is required. Send the X-Freelancer-Id header.',
            );
        }

        if (! $this->userIsMember($user, $freelancerId) && ! $user->isSuperAdmin()) {
            throw new ApiException(ApiErrorCode::Forbidden);
        }

        $freelancer = Freelancer::query()->find($freelancerId);

        if ($freelancer === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Invalid freelancer workspace.');
        }

        if ($freelancer->status === FreelancerStatus::Suspended) {
            throw new ApiException(ApiErrorCode::Forbidden, 'This workspace is suspended.');
        }

        $this->tenantContext->setFreelancerId($freelancerId);

        return $next($request);
    }

    private function resolveFreelancerId(Request $request, User $user): ?int
    {
        if ($user->isSuperAdmin() && $this->isAdminRoute($request)) {
            $override = $request->query('freelancer_id');

            if ($override !== null && $override !== '') {
                return (int) $override;
            }
        }

        $header = $request->header('X-Freelancer-Id');

        if ($header !== null && $header !== '') {
            return (int) $header;
        }

        $memberships = FreelancerMembership::query()
            ->where('user_id', $user->id)
            ->pluck('freelancer_id');

        if ($memberships->count() === 1) {
            return (int) $memberships->first();
        }

        return null;
    }

    private function userIsMember(User $user, int $freelancerId): bool
    {
        return FreelancerMembership::query()
            ->where('user_id', $user->id)
            ->where('freelancer_id', $freelancerId)
            ->exists();
    }

    private function isAdminRoute(Request $request): bool
    {
        $prefix = '/'.config('api.prefix', 'api/v1').'/admin';

        return $request->is(trim($prefix, '/').'/*')
            || $request->is(trim($prefix, '/'));
    }
}

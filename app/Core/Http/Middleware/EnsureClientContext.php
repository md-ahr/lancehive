<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\ClientPortal\ClientContext;
use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureClientContext
{
    public function __construct(private ClientContext $clientContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new ApiException(ApiErrorCode::Unauthenticated);
        }

        $clientId = $this->resolveClientId($request, $user);

        if ($clientId === null) {
            throw new ApiException(
                ApiErrorCode::Forbidden,
                'Active client organization is required. Send the X-Client-Id header.',
            );
        }

        if (! $this->userIsMember($user, $clientId)) {
            throw new ApiException(ApiErrorCode::Forbidden);
        }

        $client = Client::query()->withoutGlobalScopes()->find($clientId);

        if ($client === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Invalid client organization.');
        }

        if ($client->status === ClientStatus::Archived) {
            throw new ApiException(ApiErrorCode::Forbidden, 'This client organization is archived.');
        }

        $this->clientContext->setClientId($clientId);

        return $next($request);
    }

    private function resolveClientId(Request $request, User $user): ?int
    {
        $header = $request->header('X-Client-Id');

        if ($header !== null && $header !== '') {
            return (int) $header;
        }

        $memberships = ClientMembership::query()
            ->where('user_id', $user->id)
            ->pluck('client_id');

        if ($memberships->count() === 1) {
            return (int) $memberships->first();
        }

        return null;
    }

    private function userIsMember(User $user, int $clientId): bool
    {
        return ClientMembership::query()
            ->where('user_id', $user->id)
            ->where('client_id', $clientId)
            ->exists();
    }
}

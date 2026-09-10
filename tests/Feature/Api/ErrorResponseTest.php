<?php

declare(strict_types=1);

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\Auth\Models\User;
use Illuminate\Http\Request;

it('renders api exception with message and code for workspace read only', function () {
    $exception = new ApiException(
        ApiErrorCode::WorkspaceReadOnly,
        'This workspace is read-only. Renew your subscription to make changes.',
    );

    $response = $exception->render(Request::create('/api/v1/clients', 'POST'));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe([
            'message' => 'This workspace is read-only. Renew your subscription to make changes.',
            'code' => 'workspace_read_only',
        ]);
});

it('renders api exception with message and code for plan limit exceeded', function () {
    $exception = new ApiException(
        ApiErrorCode::PlanLimitExceeded,
        'Starter plan allows 3 clients. Upgrade to add more.',
    );

    $response = $exception->render(Request::create('/api/v1/clients', 'POST'));

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe([
            'message' => 'Starter plan allows 3 clients. Upgrade to add more.',
            'code' => 'plan_limit_exceeded',
        ]);
});

it('returns unauthenticated when accessing protected route without token', function () {
    $this->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

it('returns super admin required when non admin accesses users list', function () {
    $user = User::factory()->freelancer()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson($this->apiUrl('users'))
        ->assertForbidden();
});

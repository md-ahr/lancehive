<?php

declare(strict_types=1);

use App\Core\Http\RateLimiting\RateLimitKey;
use App\Features\Auth\Models\User;
use Illuminate\Http\Request;

it('builds rate limit keys from sanctum token id when authenticated', function () {
    $user = User::factory()->create();
    $tokenResult = $user->createToken('api');
    $userWithToken = $user->withAccessToken($tokenResult->accessToken);

    $request = Request::create('/api/v1/clients', 'GET');
    $request->setUserResolver(fn () => $userWithToken);

    expect(RateLimitKey::forUserOrIp($request))->toBe('token:'.$tokenResult->accessToken->id);
});

it('builds tenant rate limit keys from token and freelancer header', function () {
    $user = User::factory()->create();
    $tokenResult = $user->createToken('api');
    $userWithToken = $user->withAccessToken($tokenResult->accessToken);

    $request = Request::create('/api/v1/clients', 'POST', server: [
        'HTTP_X_FREELANCER_ID' => '42',
    ]);
    $request->setUserResolver(fn () => $userWithToken);

    expect(RateLimitKey::forTenant($request))->toBe('token:'.$tokenResult->accessToken->id.':freelancer:42');
});

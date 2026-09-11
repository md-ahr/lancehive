<?php

declare(strict_types=1);

namespace App\Core\Http\RateLimiting;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class RateLimitKey
{
    public static function forUserOrIp(Request $request): string
    {
        $user = $request->user();

        if ($user !== null) {
            $token = $user->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                return 'token:'.$token->id;
            }

            return 'user:'.$user->id;
        }

        return 'ip:'.$request->ip();
    }

    public static function forTenant(Request $request): string
    {
        $key = self::forUserOrIp($request);
        $freelancerId = $request->header('X-Freelancer-Id');

        if (is_string($freelancerId) && $freelancerId !== '') {
            $key .= ':freelancer:'.$freelancerId;
        }

        return $key;
    }
}

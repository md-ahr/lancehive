<?php

declare(strict_types=1);

namespace App\Core\Http\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class ConfigureRateLimiters
{
    public static function register(): void
    {
        $limits = config('rate-limiting.limits');

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute($limits['api'])
            ->by(RateLimitKey::forUserOrIp($request)));

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute($limits['login'])
            ->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute($limits['password_reset'])
            ->by($request->ip()));

        RateLimiter::for('reports', fn (Request $request): Limit => Limit::perMinute($limits['reports'])
            ->by(RateLimitKey::forTenant($request)));

        RateLimiter::for('admin', fn (Request $request): Limit => Limit::perMinute($limits['admin'])
            ->by(RateLimitKey::forUserOrIp($request)));

        RateLimiter::for('subscription', fn (Request $request): Limit => Limit::perMinute($limits['subscription'])
            ->by(RateLimitKey::forTenant($request)));

        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute($limits['webhooks'])
            ->by($request->ip()));
    }
}

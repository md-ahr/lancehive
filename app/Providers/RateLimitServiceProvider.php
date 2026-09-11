<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Http\RateLimiting\ConfigureRateLimiters;
use App\Core\Http\RateLimiting\RateLimitKey;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheRateLimiter::class, function ($app): CacheRateLimiter {
            return new CacheRateLimiter(
                $app['cache']->store((string) config('rate-limiting.store')),
            );
        });
    }

    public function boot(): void
    {
        ConfigureRateLimiters::register();

        $this->registerPlanAwareTenantWriteLimiter();
    }

    private function registerPlanAwareTenantWriteLimiter(): void
    {
        RateLimiter::for('tenant-writes', function (Request $request): Limit {
            $limit = $this->resolveTenantWriteLimit($request);

            return Limit::perMinute($limit)->by(RateLimitKey::forTenant($request));
        });
    }

    private function resolveTenantWriteLimit(Request $request): int
    {
        $baseLimit = (int) config('rate-limiting.limits.tenant_writes');
        $freelancerId = $request->header('X-Freelancer-Id');

        if (! is_string($freelancerId) || $freelancerId === '') {
            return $baseLimit;
        }

        $subscription = app(SubscriptionCache::class)->forFreelancer((int) $freelancerId);
        $planSlug = $subscription?->plan?->slug;

        if (! is_string($planSlug) || $planSlug === '') {
            return $baseLimit;
        }

        $multiplier = (float) config("rate-limiting.plan_multipliers.{$planSlug}", 1);

        return (int) max(1, round($baseLimit * $multiplier));
    }
}

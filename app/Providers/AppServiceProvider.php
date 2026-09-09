<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            return app()->isProduction()
                ? $rule->mixedCase()->symbols()->uncompromised()
                : $rule;
        });

        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-reset', fn ($request) => Limit::perMinute(3)->by($request->ip()));

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.frontend_url').'/reset-password'
                .'?token='.$token
                .'&email='.urlencode($user->email);
        });

        Gate::define('freelancer', fn (User $user) => $user->isFreelancer());
        Gate::define('client', fn (User $user) => $user->isClient());
    }
}

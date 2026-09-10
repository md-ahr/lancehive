<?php

namespace App\Providers;

use App\Core\ClientPortal\ClientContext;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Contracts\StripeGateway;
use App\Features\PlatformBilling\Services\StripeCashierGateway;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Reporting\Policies\ReportExportPolicy;
use App\Features\Reporting\Policies\SavedReportPolicy;
use App\Features\Settings\Models\WorkspaceSettings;
use App\Features\Settings\Policies\WorkspaceSettingsPolicy;
use App\Features\Tenancy\Models\Freelancer;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
        $this->app->scoped(ClientContext::class);
        $this->app->singleton(StripeGateway::class, StripeCashierGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

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

        Gate::policy(WorkspaceSettings::class, WorkspaceSettingsPolicy::class);
        Gate::policy(SavedReport::class, SavedReportPolicy::class);
        Gate::policy(ReportExport::class, ReportExportPolicy::class);

        Gate::define('super-admin', fn (User $user) => $user->isSuperAdmin());
        Gate::define('freelancer', fn (User $user) => $user->isFreelancer());
        Gate::define('client', fn (User $user) => $user->isClient());

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->info->title = config('scramble.ui.title', config('app.name').' API');
            });

        Gate::define('viewApiDocs', fn (?User $user = null) => app()->environment(['local', 'testing']));

        Cashier::useCustomerModel(Freelancer::class);
    }
}

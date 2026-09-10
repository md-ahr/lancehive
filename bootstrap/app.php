<?php

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Http\Middleware\EnsureClientContext;
use App\Core\Http\Middleware\EnsureFreelancerContext;
use App\Core\Http\Middleware\EnsureWritableSubscription;
use App\Features\ClientBilling\Jobs\MarkOverdueClientInvoices;
use App\Features\PlatformBilling\Console\NotifyTrialEndingCommand;
use App\Features\PlatformBilling\Console\SyncPlansWithStripeCommand;
use App\Features\Reporting\Console\PurgeExpiredReportExportsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        SyncPlansWithStripeCommand::class,
        NotifyTrialEndingCommand::class,
        PurgeExpiredReportExportsCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(new MarkOverdueClientInvoices)->daily();
        $schedule->command('subscriptions:notify-trial-ending')->daily();
        $schedule->command('reports:purge-expired-exports')->daily();
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'freelancer.context' => EnsureFreelancerContext::class,
            'client.context' => EnsureClientContext::class,
            'writable.subscription' => EnsureWritableSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ApiException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $exception->render($request);
            }

            return null;
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return (new ApiException(ApiErrorCode::NotFound))->render($request);
            }

            return null;
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return (new ApiException(ApiErrorCode::NotFound))->render($request);
            }

            return null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $middleware = $request->route()?->gatherMiddleware() ?? [];
            $requiresSuperAdmin = collect($middleware)->contains(
                fn (string $name): bool => str_contains($name, 'super-admin'),
            );

            if ($requiresSuperAdmin) {
                return (new ApiException(ApiErrorCode::SuperAdminRequired))->render($request);
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'This action is unauthorized.',
                'code' => ApiErrorCode::Forbidden->value,
            ], ApiErrorCode::Forbidden->httpStatus());
        });
    })->create();

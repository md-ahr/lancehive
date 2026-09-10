<?php

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Http\Middleware\EnsureFreelancerContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'freelancer.context' => EnsureFreelancerContext::class,
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

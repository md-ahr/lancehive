<?php

use App\Features\PlatformBilling\Http\Controllers\StripeWebhookController;
use App\Features\PlatformBilling\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('webhooks.stripe');

Route::middleware(['auth:sanctum', 'freelancer.context'])
    ->group(function (): void {
        Route::get('/subscription', [SubscriptionController::class, 'show'])
            ->name('subscription.show');

        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
            ->name('subscription.checkout');

        Route::post('/subscription/swap', [SubscriptionController::class, 'swap'])
            ->name('subscription.swap');

        Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
            ->name('subscription.cancel');
    });

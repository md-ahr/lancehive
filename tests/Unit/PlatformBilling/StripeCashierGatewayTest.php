<?php

declare(strict_types=1);

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Services\StripeCashierGateway;
use App\Features\Tenancy\Models\Freelancer;

it('returns forbidden when stripe price is not configured', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $plan = Plan::factory()->create([
        'stripe_price_monthly_id' => null,
    ]);

    $gateway = app(StripeCashierGateway::class);

    expect(fn () => $gateway->createCheckoutSession($freelancer, $plan, BillingInterval::Monthly))
        ->toThrow(function (ApiException $exception): void {
            expect($exception->errorCode)->toBe(ApiErrorCode::Forbidden)
                ->and($exception->errorCode->httpStatus())->toBe(403)
                ->and($exception->getMessage())->toBe('This plan is not available for checkout. Contact support.');
        });
});

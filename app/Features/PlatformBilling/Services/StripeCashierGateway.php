<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\PlatformBilling\Contracts\StripeGateway;
use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\Tenancy\Models\Freelancer;
use Stripe\Price;
use Stripe\Product;

final class StripeCashierGateway implements StripeGateway
{
    /**
     * @param  array<string, string|int>  $metadata
     */
    public function createCheckoutSession(
        Freelancer $freelancer,
        Plan $plan,
        BillingInterval $interval,
        array $metadata = [],
    ): string {
        $priceId = $this->resolvePriceId($plan, $interval);
        $freelancer->createOrGetStripeCustomer();

        $session = $freelancer->stripe()->checkout->sessions->create([
            'customer' => $freelancer->stripe_id,
            'mode' => 'subscription',
            'line_items' => [
                ['price' => $priceId, 'quantity' => 1],
            ],
            'success_url' => config('app.frontend_url').'/subscription/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.frontend_url').'/subscription/cancel',
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ]);

        return $session->url ?? throw new ApiException(ApiErrorCode::Forbidden, 'Unable to create checkout session.');
    }

    public function swapSubscription(
        Freelancer $freelancer,
        string $providerSubscriptionId,
        Plan $plan,
        BillingInterval $interval,
    ): void {
        $priceId = $this->resolvePriceId($plan, $interval);
        $subscription = $freelancer->stripe()->subscriptions->retrieve($providerSubscriptionId);
        $itemId = $subscription->items->data[0]->id ?? null;

        if ($itemId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Stripe subscription has no line items.');
        }

        $freelancer->stripe()->subscriptions->update($providerSubscriptionId, [
            'items' => [
                [
                    'id' => $itemId,
                    'price' => $priceId,
                ],
            ],
            'proration_behavior' => 'create_prorations',
        ]);
    }

    public function cancelSubscriptionAtPeriodEnd(
        Freelancer $freelancer,
        string $providerSubscriptionId,
    ): void {
        $freelancer->stripe()->subscriptions->update($providerSubscriptionId, [
            'cancel_at_period_end' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function syncPlanPrices(Plan $plan, array $options = []): Plan
    {
        $currency = strtolower((string) ($options['currency'] ?? $plan->currency ?? 'bdt'));
        $productId = $options['product_id'] ?? null;

        if ($productId === null) {
            $product = Product::create([
                'name' => $plan->name,
                'metadata' => ['plan_id' => (string) $plan->id, 'slug' => $plan->slug],
            ]);
            $productId = $product->id;
        }

        if ($plan->price_monthly !== null && $plan->stripe_price_monthly_id === null) {
            $monthly = Price::create([
                'product' => $productId,
                'unit_amount' => (int) round((float) $plan->price_monthly * 100),
                'currency' => $currency,
                'recurring' => ['interval' => 'month'],
            ]);
            $plan->stripe_price_monthly_id = $monthly->id;
        }

        if ($plan->price_yearly !== null && $plan->stripe_price_yearly_id === null) {
            $yearly = Price::create([
                'product' => $productId,
                'unit_amount' => (int) round((float) $plan->price_yearly * 100),
                'currency' => $currency,
                'recurring' => ['interval' => 'year'],
            ]);
            $plan->stripe_price_yearly_id = $yearly->id;
        }

        $plan->save();

        return $plan->fresh();
    }

    private function resolvePriceId(Plan $plan, BillingInterval $interval): string
    {
        $priceId = match ($interval) {
            BillingInterval::Monthly => $plan->stripe_price_monthly_id,
            BillingInterval::Yearly => $plan->stripe_price_yearly_id,
        };

        if ($priceId === null || $priceId === '') {
            throw new ApiException(
                ApiErrorCode::Forbidden,
                'This plan is not available for checkout. Contact support.',
            );
        }

        return $priceId;
    }
}

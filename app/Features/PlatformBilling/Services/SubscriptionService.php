<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use App\Features\PlatformBilling\Contracts\StripeGateway;
use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use App\Features\PlatformBilling\Notifications\PaymentFailedNotification;
use App\Features\PlatformBilling\Notifications\RenewalReceiptNotification;
use App\Features\PlatformBilling\Notifications\SubscriptionCanceledNotification;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class SubscriptionService
{
    public function __construct(
        private readonly StripeGateway $stripeGateway,
        private readonly SubscriptionCache $subscriptionCache,
    ) {}

    public function startTrial(Freelancer $freelancer, Plan $plan, int $days): Subscription
    {
        $subscription = DB::transaction(function () use ($freelancer, $plan, $days): Subscription {
            $subscription = Subscription::query()->updateOrCreate(
                ['freelancer_id' => $freelancer->id],
                [
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Trialing,
                    'billing_interval' => null,
                    'trial_ends_at' => now()->addDays($days),
                    'trial_ending_notified_at' => null,
                    'current_period_start' => null,
                    'current_period_end' => null,
                    'read_only_at' => null,
                    'canceled_at' => null,
                    'provider' => SubscriptionProvider::Manual,
                    'provider_subscription_id' => null,
                ],
            );

            return $subscription;
        });

        $this->subscriptionCache->forget($freelancer->id);

        return $subscription->load('plan');
    }

    public function createCheckoutSession(
        Freelancer $freelancer,
        Plan $plan,
        BillingInterval $interval,
    ): string {
        if ($plan->is_custom) {
            throw new ApiException(ApiErrorCode::PlanLimitExceeded, 'Custom plans cannot be purchased via checkout.');
        }

        $url = $this->stripeGateway->createCheckoutSession($freelancer, $plan, $interval, [
            'freelancer_id' => (string) $freelancer->id,
            'plan_id' => (string) $plan->id,
            'billing_interval' => $interval->value,
        ]);

        return $url;
    }

    public function swapPlan(
        Freelancer $freelancer,
        Plan $plan,
        BillingInterval $interval,
    ): Subscription {
        $subscription = $this->requireStripeSubscription($freelancer);

        $this->stripeGateway->swapSubscription(
            $freelancer,
            (string) $subscription->provider_subscription_id,
            $plan,
            $interval,
        );

        $subscription->update([
            'plan_id' => $plan->id,
            'billing_interval' => $interval,
        ]);

        $this->subscriptionCache->forget($freelancer->id);

        return $subscription->fresh('plan');
    }

    public function cancel(Freelancer $freelancer): Subscription
    {
        $subscription = $this->requireStripeSubscription($freelancer);

        $this->stripeGateway->cancelSubscriptionAtPeriodEnd(
            $freelancer,
            (string) $subscription->provider_subscription_id,
        );

        $subscription->update([
            'canceled_at' => now(),
        ]);

        $this->notifyOwner($freelancer, new SubscriptionCanceledNotification($subscription->fresh('plan')));

        $this->subscriptionCache->forget($freelancer->id);

        return $subscription->fresh('plan');
    }

    public function assignCustomPlan(
        Freelancer $freelancer,
        Plan $plan,
        SubscriptionProvider $provider = SubscriptionProvider::Manual,
    ): Subscription {
        $subscription = DB::transaction(function () use ($freelancer, $plan, $provider): Subscription {
            return Subscription::query()->updateOrCreate(
                ['freelancer_id' => $freelancer->id],
                [
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Active,
                    'billing_interval' => null,
                    'trial_ends_at' => null,
                    'current_period_start' => now(),
                    'current_period_end' => null,
                    'read_only_at' => null,
                    'canceled_at' => null,
                    'provider' => $provider,
                    'provider_subscription_id' => null,
                ],
            );
        });

        $this->subscriptionCache->forget($freelancer->id);

        return $subscription->load('plan');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function syncFromStripeWebhook(array $payload): void
    {
        $type = (string) ($payload['type'] ?? '');
        $object = $payload['data']['object'] ?? [];

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object),
            'invoice.paid' => $this->handleInvoicePaid($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function handleCheckoutCompleted(array $session): void
    {
        $freelancerId = (int) ($session['metadata']['freelancer_id'] ?? 0);
        $planId = (int) ($session['metadata']['plan_id'] ?? 0);
        $interval = BillingInterval::tryFrom((string) ($session['metadata']['billing_interval'] ?? ''));
        $providerSubscriptionId = (string) ($session['subscription'] ?? '');

        if ($freelancerId === 0 || $planId === 0 || $providerSubscriptionId === '' || $interval === null) {
            return;
        }

        $status = SubscriptionStatus::Active;

        Subscription::query()->where('freelancer_id', $freelancerId)->update([
            'plan_id' => $planId,
            'status' => $status,
            'billing_interval' => $interval,
            'trial_ends_at' => null,
            'provider' => SubscriptionProvider::Stripe,
            'provider_subscription_id' => $providerSubscriptionId,
            'read_only_at' => null,
            'canceled_at' => null,
        ]);

        $this->subscriptionCache->forget($freelancerId);
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function handleInvoicePaid(array $invoice): void
    {
        $subscription = $this->resolveSubscriptionFromStripeInvoice($invoice);

        if ($subscription === null) {
            return;
        }

        $chargeId = (string) ($invoice['id'] ?? '');

        if ($chargeId !== '' && SubscriptionCharge::query()->where('provider_charge_id', $chargeId)->exists()) {
            return;
        }

        $periodStart = isset($invoice['period_start']) ? Carbon::createFromTimestamp((int) $invoice['period_start']) : null;
        $periodEnd = isset($invoice['period_end']) ? Carbon::createFromTimestamp((int) $invoice['period_end']) : null;

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'read_only_at' => null,
        ]);

        SubscriptionCharge::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => ((int) ($invoice['amount_paid'] ?? 0)) / 100,
            'currency' => strtoupper((string) ($invoice['currency'] ?? 'BDT')),
            'status' => SubscriptionChargeStatus::Paid,
            'paid_at' => now(),
            'provider_charge_id' => $chargeId,
        ]);

        $this->notifyOwner($subscription->freelancer, new RenewalReceiptNotification($subscription->fresh('plan')));

        $this->subscriptionCache->forget($subscription->freelancer_id);
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function handleInvoicePaymentFailed(array $invoice): void
    {
        $subscription = $this->resolveSubscriptionFromStripeInvoice($invoice);

        if ($subscription === null) {
            return;
        }

        $chargeId = (string) ($invoice['id'] ?? '');

        if ($chargeId !== '' && SubscriptionCharge::query()->where('provider_charge_id', $chargeId)->exists()) {
            return;
        }

        $attemptCount = (int) ($invoice['attempt_count'] ?? 1);
        $status = $attemptCount >= 3 ? SubscriptionStatus::ReadOnly : SubscriptionStatus::PastDue;

        $subscription->update([
            'status' => $status,
            'read_only_at' => $status === SubscriptionStatus::ReadOnly ? now() : $subscription->read_only_at,
        ]);

        SubscriptionCharge::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => ((int) ($invoice['amount_due'] ?? 0)) / 100,
            'currency' => strtoupper((string) ($invoice['currency'] ?? 'BDT')),
            'status' => SubscriptionChargeStatus::Failed,
            'paid_at' => null,
            'provider_charge_id' => $chargeId,
        ]);

        $this->notifyOwner($subscription->freelancer, new PaymentFailedNotification($subscription->fresh('plan')));

        $this->subscriptionCache->forget($subscription->freelancer_id);
    }

    /**
     * @param  array<string, mixed>  $stripeSubscription
     */
    private function handleSubscriptionUpdated(array $stripeSubscription): void
    {
        $subscription = $this->resolveSubscriptionFromStripeObject($stripeSubscription);

        if ($subscription === null) {
            return;
        }

        $status = $this->mapStripeStatus((string) ($stripeSubscription['status'] ?? ''));
        $periodStart = isset($stripeSubscription['current_period_start'])
            ? Carbon::createFromTimestamp((int) $stripeSubscription['current_period_start'])
            : null;
        $periodEnd = isset($stripeSubscription['current_period_end'])
            ? Carbon::createFromTimestamp((int) $stripeSubscription['current_period_end'])
            : null;

        $subscription->update([
            'status' => $status,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'canceled_at' => ($stripeSubscription['cancel_at_period_end'] ?? false) ? ($subscription->canceled_at ?? now()) : null,
            'read_only_at' => $status === SubscriptionStatus::ReadOnly ? ($subscription->read_only_at ?? now()) : null,
        ]);

        $this->subscriptionCache->forget($subscription->freelancer_id);
    }

    /**
     * @param  array<string, mixed>  $stripeSubscription
     */
    private function handleSubscriptionDeleted(array $stripeSubscription): void
    {
        $subscription = $this->resolveSubscriptionFromStripeObject($stripeSubscription);

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'read_only_at' => now(),
        ]);

        $this->notifyOwner($subscription->freelancer, new SubscriptionCanceledNotification($subscription->fresh('plan')));

        $this->subscriptionCache->forget($subscription->freelancer_id);
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function resolveSubscriptionFromStripeInvoice(array $invoice): ?Subscription
    {
        $providerSubscriptionId = (string) ($invoice['subscription'] ?? '');

        if ($providerSubscriptionId === '') {
            return null;
        }

        return Subscription::query()
            ->where('provider_subscription_id', $providerSubscriptionId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $stripeSubscription
     */
    private function resolveSubscriptionFromStripeObject(array $stripeSubscription): ?Subscription
    {
        $providerSubscriptionId = (string) ($stripeSubscription['id'] ?? '');

        if ($providerSubscriptionId === '') {
            return null;
        }

        return Subscription::query()
            ->where('provider_subscription_id', $providerSubscriptionId)
            ->first();
    }

    private function mapStripeStatus(string $stripeStatus): SubscriptionStatus
    {
        return match ($stripeStatus) {
            'trialing' => SubscriptionStatus::Trialing,
            'active' => SubscriptionStatus::Active,
            'past_due' => SubscriptionStatus::PastDue,
            'canceled', 'unpaid' => SubscriptionStatus::Canceled,
            default => SubscriptionStatus::ReadOnly,
        };
    }

    private function requireStripeSubscription(Freelancer $freelancer): Subscription
    {
        $subscription = Subscription::query()
            ->where('freelancer_id', $freelancer->id)
            ->first();

        if ($subscription === null || $subscription->provider_subscription_id === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'No active Stripe subscription found.');
        }

        return $subscription;
    }

    private function notifyOwner(Freelancer $freelancer, object $notification): void
    {
        $owner = $freelancer->owner;

        if ($owner instanceof User) {
            Notification::send($owner, $notification);
        }
    }
}

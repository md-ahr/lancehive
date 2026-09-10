<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Tenancy\TenantContext;
use App\Features\PlatformBilling\Cache\SubscriptionCache;
use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Http\Requests\CancelSubscriptionRequest;
use App\Features\PlatformBilling\Http\Requests\CheckoutSubscriptionRequest;
use App\Features\PlatformBilling\Http\Requests\ShowSubscriptionRequest;
use App\Features\PlatformBilling\Http\Requests\SwapSubscriptionRequest;
use App\Features\PlatformBilling\Http\Resources\CheckoutResource;
use App\Features\PlatformBilling\Http\Resources\SubscriptionDetailResource;
use App\Features\PlatformBilling\Http\Resources\SubscriptionResource;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Services\SubscriptionService;
use App\Features\Tenancy\Models\Freelancer;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Subscriptions', weight: 60)]
final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionCache $subscriptionCache,
        private readonly TenantContext $tenantContext,
    ) {}

    public function show(ShowSubscriptionRequest $request): SubscriptionDetailResource
    {
        $subscription = $this->resolveSubscription();

        return new SubscriptionDetailResource($subscription);
    }

    public function checkout(CheckoutSubscriptionRequest $request): CheckoutResource
    {
        $freelancer = $this->resolveFreelancer();
        $plan = Plan::query()->findOrFail((int) $request->validated('plan_id'));
        $interval = BillingInterval::from($request->validated('billing_interval'));

        $url = $this->subscriptionService->createCheckoutSession($freelancer, $plan, $interval);

        return new CheckoutResource($url);
    }

    public function swap(SwapSubscriptionRequest $request): SubscriptionResource
    {
        $freelancer = $this->resolveFreelancer();
        $plan = Plan::query()->findOrFail((int) $request->validated('plan_id'));
        $interval = BillingInterval::from($request->validated('billing_interval'));

        $subscription = $this->subscriptionService->swapPlan($freelancer, $plan, $interval);

        return new SubscriptionResource($subscription);
    }

    public function cancel(CancelSubscriptionRequest $request): SubscriptionResource
    {
        $freelancer = $this->resolveFreelancer();
        $subscription = $this->subscriptionService->cancel($freelancer);

        return new SubscriptionResource($subscription);
    }

    private function resolveFreelancer(): Freelancer
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        return Freelancer::query()->findOrFail($freelancerId);
    }

    private function resolveSubscription(): Subscription
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $subscription = $this->subscriptionCache->forFreelancer($freelancerId);

        if ($subscription === null) {
            throw new ApiException(ApiErrorCode::NotFound, 'No subscription found for this workspace.');
        }

        return $subscription;
    }
}

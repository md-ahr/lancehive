<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Controllers;

use App\Features\Admin\Http\Requests\AssignFreelancerSubscriptionRequest;
use App\Features\Admin\Services\AdminActivityLogger;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Http\Resources\SubscriptionResource;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Services\SubscriptionService;
use App\Features\Tenancy\Models\Freelancer;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Admin', weight: 10)]
final class FreelancerSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly AdminActivityLogger $activityLogger,
    ) {}

    public function update(AssignFreelancerSubscriptionRequest $request, Freelancer $freelancer): JsonResponse
    {
        $plan = Plan::query()->findOrFail((int) $request->validated('plan_id'));
        $provider = SubscriptionProvider::from($request->validated('provider', SubscriptionProvider::Manual->value));

        $subscription = $this->subscriptionService->assignCustomPlan($freelancer, $plan, $provider);

        $this->activityLogger->log(
            $request->user(),
            'freelancer.subscription.assign',
            $freelancer,
            [
                'plan_id' => $plan->id,
                'provider' => $provider->value,
            ],
            $request,
        );

        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(200);
    }
}

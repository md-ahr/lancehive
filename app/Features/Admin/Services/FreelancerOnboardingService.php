<?php

declare(strict_types=1);

namespace App\Features\Admin\Services;

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Cache\MembershipCache;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Database\Seeders\Support\DemoData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FreelancerOnboardingService
{
    private const int DEFAULT_TRIAL_DAYS = 14;

    public function __construct(private readonly MembershipCache $membershipCache) {}

    /**
     * @param  array{
     *     workspace_name: string,
     *     owner_name: string,
     *     owner_email: string,
     *     plan_id?: int|null,
     *     trial_days?: int|null
     * }  $data
     */
    public function onboard(array $data): Freelancer
    {
        return DB::transaction(function () use ($data): Freelancer {
            $owner = User::query()->create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Str::password(),
                'role' => UserRole::User,
            ]);

            $freelancer = Freelancer::query()->create([
                'name' => $data['workspace_name'],
                'slug' => $this->uniqueSlug($data['workspace_name']),
                'status' => FreelancerStatus::Pending,
                'owner_user_id' => $owner->id,
            ]);

            FreelancerMembership::query()->create([
                'freelancer_id' => $freelancer->id,
                'user_id' => $owner->id,
                'role' => FreelancerMembershipRole::Owner,
            ]);

            $plan = $this->resolvePlan($data['plan_id'] ?? null);
            $trialDays = $data['trial_days'] ?? self::DEFAULT_TRIAL_DAYS;

            Subscription::query()->create([
                'freelancer_id' => $freelancer->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Trialing,
                'billing_interval' => null,
                'trial_ends_at' => now()->addDays($trialDays),
                'current_period_start' => null,
                'current_period_end' => null,
                'read_only_at' => null,
                'canceled_at' => null,
                'provider' => SubscriptionProvider::Manual,
                'provider_subscription_id' => null,
            ]);

            $this->membershipCache->forget($owner->id);

            return $freelancer->fresh(['owner', 'subscription.plan']);
        });
    }

    private function resolvePlan(?int $planId): Plan
    {
        if ($planId !== null) {
            return Plan::query()->findOrFail($planId);
        }

        return Plan::query()
            ->where('slug', DemoData::PLAN_STARTER_SLUG)
            ->firstOrFail();
    }

    private function uniqueSlug(string $workspaceName): string
    {
        $base = Str::slug($workspaceName);
        $slug = $base;
        $suffix = 1;

        while (Freelancer::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}

<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Services;

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Services\PlanLimitService;
use App\Features\Tenancy\Cache\MembershipCache;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TeamMemberInviteService
{
    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly MembershipCache $membershipCache,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     role: FreelancerMembershipRole
     * }  $data
     */
    public function invite(int $freelancerId, array $data): FreelancerMembership
    {
        return DB::transaction(function () use ($freelancerId, $data): FreelancerMembership {
            $this->planLimitService->assertCanAddTeamMember($freelancerId);

            $email = Str::lower($data['email']);
            $user = User::query()->where('email', $email)->first();

            if ($user !== null) {
                $existingMembership = FreelancerMembership::query()
                    ->where('freelancer_id', $freelancerId)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($existingMembership) {
                    throw ValidationException::withMessages([
                        'email' => ['This user is already a member of the workspace.'],
                    ]);
                }
            } else {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => Str::password(),
                    'role' => UserRole::User,
                ]);
            }

            $membership = FreelancerMembership::query()->create([
                'freelancer_id' => $freelancerId,
                'user_id' => $user->id,
                'role' => $data['role'],
            ]);

            $this->membershipCache->forget($user->id);

            return $membership->load('user');
        });
    }
}

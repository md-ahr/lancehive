<?php

declare(strict_types=1);

namespace Database\Factories\Tenancy;

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreelancerMembership>
 */
class FreelancerMembershipFactory extends Factory
{
    protected $model = FreelancerMembership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => Freelancer::factory(),
            'user_id' => User::factory(),
            'role' => FreelancerMembershipRole::Member,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => FreelancerMembershipRole::Owner]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => FreelancerMembershipRole::Admin]);
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => FreelancerMembershipRole::Member]);
    }
}

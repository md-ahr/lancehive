<?php

declare(strict_types=1);

namespace Database\Factories\Tenancy;

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Freelancer>
 */
class FreelancerFactory extends Factory
{
    protected $model = Freelancer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'status' => FreelancerStatus::Active,
            'owner_user_id' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => FreelancerStatus::Pending]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => FreelancerStatus::Active]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => FreelancerStatus::Suspended]);
    }
}

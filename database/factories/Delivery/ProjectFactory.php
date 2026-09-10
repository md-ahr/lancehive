<?php

declare(strict_types=1);

namespace Database\Factories\Delivery;

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'freelancer_id' => Freelancer::factory(),
            'name' => fake()->catchPhrase(),
            'hourly_rate' => fake()->randomFloat(2, 500, 5000),
            'currency' => 'BDT',
            'status' => ProjectStatus::Active,
            'deadline' => fake()->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }

    public function onHold(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::OnHold]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Completed]);
    }
}

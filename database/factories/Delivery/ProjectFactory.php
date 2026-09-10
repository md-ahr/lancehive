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
            'freelancer_id' => Freelancer::factory(),
            'client_id' => Client::factory(),
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

    public function configure(): static
    {
        return $this->afterCreating(function (Project $project): void {
            $client = Client::withoutGlobalScopes()->find($project->client_id);

            if ($client !== null && $client->freelancer_id !== $project->freelancer_id) {
                $client->forceFill(['freelancer_id' => $project->freelancer_id])->saveQuietly();
            }
        });
    }
}

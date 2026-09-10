<?php

declare(strict_types=1);

namespace Database\Factories\Delivery;

use App\Features\Delivery\Enums\TaskStatus;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'status' => TaskStatus::Todo,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'estimated_hours' => fake()->optional()->randomFloat(2, 1, 40),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::InProgress]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Done]);
    }
}

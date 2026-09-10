<?php

declare(strict_types=1);

namespace Database\Factories\Delivery;

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeLog>
 */
class TimeLogFactory extends Factory
{
    protected $model = TimeLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'description' => fake()->optional()->sentence(),
            'logged_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'client_invoice_item_id' => null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories\PlatformBilling;

use App\Features\PlatformBilling\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'price_monthly' => fake()->randomFloat(2, 200, 2000),
            'price_yearly' => fake()->randomFloat(2, 2000, 20000),
            'currency' => 'BDT',
            'max_clients' => fake()->numberBetween(3, 30),
            'max_projects' => fake()->numberBetween(5, 100),
            'max_team_members' => fake()->numberBetween(1, 10),
            'is_custom' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'stripe_price_monthly_id' => null,
            'stripe_price_yearly_id' => null,
        ];
    }

    public function custom(): static
    {
        return $this->state(fn () => [
            'name' => 'Custom',
            'slug' => 'custom-'.fake()->unique()->numerify('####'),
            'price_monthly' => null,
            'price_yearly' => null,
            'max_clients' => null,
            'max_projects' => null,
            'max_team_members' => null,
            'is_custom' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

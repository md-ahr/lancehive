<?php

declare(strict_types=1);

namespace Database\Factories\Delivery;

use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => Freelancer::factory(),
            'name' => fake()->company(),
            'status' => ClientStatus::Active,
            'contact_email' => fake()->companyEmail(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => ClientStatus::Archived]);
    }
}

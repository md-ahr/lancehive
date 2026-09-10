<?php

declare(strict_types=1);

namespace Database\Factories\ClientPortal;

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientMembership>
 */
class ClientMembershipFactory extends Factory
{
    protected $model = ClientMembership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'role' => ClientMembershipRole::Viewer,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['role' => ClientMembershipRole::Primary]);
    }
}

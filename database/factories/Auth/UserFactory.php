<?php

declare(strict_types=1);

namespace Database\Factories\Auth;

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::User,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => UserRole::SuperAdmin]);
    }

    public function user(): static
    {
        return $this->state(fn () => ['role' => UserRole::User]);
    }

    /**
     * @deprecated Use user() — workspace access is determined by freelancer_memberships.
     */
    public function freelancer(): static
    {
        return $this->user();
    }

    /**
     * @deprecated Use user() — portal access is determined by client_memberships.
     */
    public function client(): static
    {
        return $this->user();
    }
}

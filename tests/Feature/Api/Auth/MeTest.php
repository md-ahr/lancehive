<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_freelancer_can_access_me_endpoint(): void
    {
        $freelancer = User::factory()->freelancer()->create([
            'email' => 'freelancer@example.com',
        ]);

        Sanctum::actingAs($freelancer);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $freelancer->id)
            ->assertJsonPath('user.email', 'freelancer@example.com')
            ->assertJsonPath('user.role', UserRole::Freelancer->value);
    }

    public function test_authenticated_client_can_access_me_endpoint(): void
    {
        $client = User::factory()->client()->create([
            'email' => 'client@example.com',
        ]);

        Sanctum::actingAs($client);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $client->id)
            ->assertJsonPath('user.email', 'client@example.com')
            ->assertJsonPath('user.role', UserRole::Client->value);
    }

    public function test_me_endpoint_works_with_bearer_token_from_login(): void
    {
        User::factory()->client()->create([
            'email' => 'client@example.com',
            'password' => 'password',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'client@example.com',
            'password' => 'password',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'client@example.com')
            ->assertJsonPath('user.role', UserRole::Client->value);
    }
}

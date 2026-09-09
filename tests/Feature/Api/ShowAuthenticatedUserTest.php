<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowAuthenticatedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_users_endpoint(): void
    {
        $this->getJson('/api/users')
            ->assertUnauthorized();
    }

    public function test_client_cannot_access_freelancer_only_users_endpoint(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());

        $this->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_freelancer_can_list_all_users(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $clients = User::factory()->client()->count(2)->create();

        Sanctum::actingAs($freelancer);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure([
                'users' => [
                    '*' => ['id', 'name', 'email', 'role'],
                ],
            ])
            ->assertJsonCount(3, 'users')
            ->assertJsonFragment([
                'id' => $freelancer->id,
                'email' => $freelancer->email,
                'role' => UserRole::Freelancer->value,
            ])
            ->assertJsonFragment([
                'id' => $clients[0]->id,
                'role' => UserRole::Client->value,
            ]);
    }

    public function test_freelancer_can_list_all_users_with_bearer_token(): void
    {
        User::factory()->client()->count(2)->create();
        $freelancer = User::factory()->freelancer()->create();
        $token = $freelancer->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'users');
    }
}

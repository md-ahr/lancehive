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

    public function test_client_cannot_access_users_endpoint(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());

        $this->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_freelancer_cannot_access_users_endpoint(): void
    {
        Sanctum::actingAs(User::factory()->freelancer()->create());

        $this->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_list_all_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $freelancer = User::factory()->freelancer()->create();
        $client = User::factory()->client()->create();

        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure([
                'users' => [
                    '*' => ['id', 'name', 'email', 'role'],
                ],
            ])
            ->assertJsonCount(3, 'users')
            ->assertJsonFragment([
                'id' => $superAdmin->id,
                'email' => $superAdmin->email,
                'role' => UserRole::SuperAdmin->value,
            ])
            ->assertJsonFragment([
                'id' => $freelancer->id,
                'role' => UserRole::Freelancer->value,
            ])
            ->assertJsonFragment([
                'id' => $client->id,
                'role' => UserRole::Client->value,
            ]);
    }

    public function test_super_admin_can_list_all_users_with_bearer_token(): void
    {
        User::factory()->freelancer()->create();
        User::factory()->client()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $token = $superAdmin->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'users');
    }
}

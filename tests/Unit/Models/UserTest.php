<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_is_cast_to_enum(): void
    {
        $user = User::factory()->freelancer()->create();

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertSame(UserRole::Freelancer, $user->role);
    }

    public function test_is_freelancer_returns_true_for_freelancer(): void
    {
        $user = User::factory()->freelancer()->create();

        $this->assertTrue($user->isFreelancer());
        $this->assertFalse($user->isClient());
    }

    public function test_is_client_returns_true_for_client(): void
    {
        $user = User::factory()->client()->create();

        $this->assertTrue($user->isClient());
        $this->assertFalse($user->isFreelancer());
        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_is_super_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->isSuperAdmin());
        $this->assertFalse($user->isFreelancer());
        $this->assertFalse($user->isClient());
    }
}

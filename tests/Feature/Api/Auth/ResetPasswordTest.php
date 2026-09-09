<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'old-password',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;
        $resetToken = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'user@example.com',
            'token' => $resetToken,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successfully.');

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword1', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        auth()->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/reset-password', [
            'email' => 'user@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_requires_matching_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $resetToken = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'user@example.com',
            'token' => $resetToken,
            'password' => 'newpassword1',
            'password_confirmation' => 'differentpassword1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_rejects_weak_password(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $resetToken = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'user@example.com',
            'token' => $resetToken,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_requires_all_fields(): void
    {
        $this->postJson('/api/reset-password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }
}

<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_notification_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'user@example.com'])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'If that email address exists, a password reset link has been sent.'
            );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_returns_same_message_for_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'If that email address exists, a password reset link has been sent.'
            );

        Notification::assertNothingSent();
    }

    public function test_forgot_password_requires_email(): void
    {
        $this->postJson('/api/forgot-password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_requires_valid_email_format(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}

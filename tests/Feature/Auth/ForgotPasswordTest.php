<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('sends reset notification for existing user on forgot password', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->postJson($this->apiUrl('forgot-password'), ['email' => 'user@example.com'])
        ->assertOk()
        ->assertJsonPath(
            'message',
            'If that email address exists, a password reset link has been sent.'
        );

    Notification::assertSentTo($user, ResetPassword::class);
});

it('returns same message for unknown email on forgot password', function () {
    Notification::fake();

    $this->postJson($this->apiUrl('forgot-password'), ['email' => 'missing@example.com'])
        ->assertOk()
        ->assertJsonPath(
            'message',
            'If that email address exists, a password reset link has been sent.'
        );

    Notification::assertNothingSent();
});

it('requires email for forgot password', function () {
    $this->postJson($this->apiUrl('forgot-password'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires valid email format for forgot password', function () {
    $this->postJson($this->apiUrl('forgot-password'), ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

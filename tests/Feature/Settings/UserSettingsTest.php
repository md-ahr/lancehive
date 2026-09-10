<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to user settings', function () {
    $this->getJson($this->apiUrl('me/settings'))
        ->assertUnauthorized();
});

it('returns user settings for authenticated user', function () {
    Sanctum::actingAs(User::factory()->create([
        'timezone' => 'Asia/Dhaka',
        'locale' => 'en',
    ]));

    $this->getJson($this->apiUrl('me/settings'))
        ->assertOk()
        ->assertJsonPath('timezone', 'Asia/Dhaka')
        ->assertJsonPath('locale', 'en')
        ->assertJsonPath('notification_preferences.subscription_alerts', true);
});

it('patches user settings partially', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson($this->apiUrl('me/settings'), [
        'timezone' => 'Europe/London',
        'notification_preferences' => [
            'workspace_invites' => false,
        ],
    ])
        ->assertOk()
        ->assertJsonPath('timezone', 'Europe/London')
        ->assertJsonPath('notification_preferences.workspace_invites', false)
        ->assertJsonPath('notification_preferences.subscription_alerts', true);
});

it('returns validation error for invalid timezone', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson($this->apiUrl('me/settings'), [
        'timezone' => 'Invalid/Zone',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['timezone']);
});

it('returns current settings when patch body is empty', function () {
    Sanctum::actingAs(User::factory()->create([
        'timezone' => 'UTC',
    ]));

    $this->patchJson($this->apiUrl('me/settings'), [])
        ->assertOk()
        ->assertJsonPath('timezone', 'UTC');
});

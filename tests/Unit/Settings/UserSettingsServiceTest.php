<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Settings\Services\UserSettingsService;
use Illuminate\Validation\ValidationException;

it('returns user settings defaults', function () {
    $user = User::factory()->create();

    $settings = app(UserSettingsService::class)->get($user);

    expect($settings['timezone'])->toBe('UTC')
        ->and($settings['locale'])->toBe('en')
        ->and($settings['notification_preferences']['subscription_alerts'])->toBeTrue();
});

it('merges partial notification preferences on update', function () {
    $user = User::factory()->create();
    $service = app(UserSettingsService::class);

    $updated = $service->update($user, [
        'notification_preferences' => [
            'subscription_alerts' => false,
            'unknown_key' => false,
        ],
    ]);

    expect($updated->notification_preferences)->toBe([
        'subscription_alerts' => false,
        'workspace_invites' => true,
        'invoice_activity' => true,
    ]);
});

it('rejects invalid timezone', function () {
    $user = User::factory()->create();

    expect(fn () => app(UserSettingsService::class)->update($user, [
        'timezone' => 'Not/A_Timezone',
    ]))->toThrow(ValidationException::class);
});

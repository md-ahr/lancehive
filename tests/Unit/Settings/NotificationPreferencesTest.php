<?php

declare(strict_types=1);

use App\Features\Settings\Support\NotificationPreferences;

it('returns default notification preferences', function () {
    expect(NotificationPreferences::defaults())->toBe([
        'subscription_alerts' => true,
        'workspace_invites' => true,
        'invoice_activity' => true,
    ]);
});

it('strips unknown keys when normalizing preferences', function () {
    $normalized = NotificationPreferences::normalize([
        'subscription_alerts' => false,
        'unknown_key' => true,
    ]);

    expect($normalized)->toBe([
        'subscription_alerts' => false,
        'workspace_invites' => true,
        'invoice_activity' => true,
    ]);
});

it('merges partial notification preferences', function () {
    $merged = NotificationPreferences::merge(
        NotificationPreferences::defaults(),
        ['workspace_invites' => false],
    );

    expect($merged['workspace_invites'])->toBeFalse()
        ->and($merged['subscription_alerts'])->toBeTrue();
});

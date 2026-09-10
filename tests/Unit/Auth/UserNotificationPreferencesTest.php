<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Settings\Support\NotificationPreferences;
use App\Features\Tenancy\Models\Freelancer;

it('honors subscription alert preference for workspace owners', function () {
    $workspace = test()->createTenantWorkspace();
    $workspace['user']->update([
        'notification_preferences' => [
            'subscription_alerts' => false,
            'workspace_invites' => true,
            'invoice_activity' => true,
        ],
    ]);

    expect($workspace['user']->fresh()->prefersNotification(
        NotificationPreferences::SUBSCRIPTION_ALERTS,
        $workspace['freelancer'],
    ))->toBeFalse();
});

it('ignores subscription alert preference for non-owners', function () {
    $freelancer = Freelancer::factory()->active()->create();
    $member = User::factory()->create([
        'notification_preferences' => [
            'subscription_alerts' => true,
            'workspace_invites' => true,
            'invoice_activity' => true,
        ],
    ]);

    expect($member->prefersNotification(
        NotificationPreferences::SUBSCRIPTION_ALERTS,
        $freelancer,
    ))->toBeFalse();
});

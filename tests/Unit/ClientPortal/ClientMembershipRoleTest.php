<?php

declare(strict_types=1);

use App\Features\ClientPortal\Enums\ClientMembershipRole;

it('lists invitable client membership roles', function () {
    expect(ClientMembershipRole::values())->toBe(['primary', 'member', 'viewer'])
        ->and(ClientMembershipRole::Primary->isInvitable())->toBeTrue()
        ->and(ClientMembershipRole::Viewer->isInvitable())->toBeTrue();
});

<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;

it('relates client memberships to users and clients', function () {
    $membership = ClientMembership::factory()->create();

    expect($membership->client)->toBeInstanceOf(Client::class)
        ->and($membership->user)->toBeInstanceOf(User::class)
        ->and($membership->role)->not->toBeNull();
});

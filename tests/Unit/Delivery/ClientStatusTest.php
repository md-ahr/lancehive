<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;

it('exposes all client status values', function () {
    expect(ClientStatus::values())->toBe(['active', 'archived']);
});

it('round trips client status through the model cast', function () {
    $client = Client::factory()->create(['status' => ClientStatus::Archived]);

    expect($client->status)->toBe(ClientStatus::Archived)
        ->and($client->getAttributes()['status'])->toBe('archived');
});

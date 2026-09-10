<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Http\Resources\ClientResource;
use App\Features\Delivery\Models\Client;

it('serializes client resource with expected keys', function () {
    $client = Client::factory()->create([
        'name' => 'BigCo Ltd',
        'status' => ClientStatus::Active,
        'contact_email' => 'billing@bigco.com',
    ]);

    $payload = (new ClientResource($client))->resolve();

    expect($payload)->toMatchArray([
        'id' => $client->id,
        'name' => 'BigCo Ltd',
        'status' => 'active',
        'contact_email' => 'billing@bigco.com',
    ])
        ->and($payload)->toHaveKeys(['created_at', 'updated_at']);
});

it('serializes archived status as snake_case string', function () {
    $client = Client::factory()->archived()->create();

    $payload = (new ClientResource($client))->resolve();

    expect($payload['status'])->toBe('archived');
});

it('serializes null contact email', function () {
    $client = Client::factory()->create(['contact_email' => null]);

    $payload = (new ClientResource($client))->resolve();

    expect($payload['contact_email'])->toBeNull();
});

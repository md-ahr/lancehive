<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\ClientPortal\Services\ClientMemberInviteService;
use App\Features\Delivery\Models\Client;
use Illuminate\Validation\ValidationException;

it('creates a client membership for a new user', function () {
    $client = Client::factory()->create();
    $service = app(ClientMemberInviteService::class);

    $result = $service->invite($client, [
        'name' => 'Portal User',
        'email' => 'portal.user@client.test',
        'role' => ClientMembershipRole::Viewer,
    ]);

    expect($result->createdNewUser)->toBeTrue()
        ->and($result->membership->client_id)->toBe($client->id)
        ->and($result->membership->role)->toBe(ClientMembershipRole::Viewer)
        ->and(User::query()->where('email', 'portal.user@client.test')->exists())->toBeTrue();
});

it('reuses an existing user without creating a new account', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create(['email' => 'existing@client.test']);
    $service = app(ClientMemberInviteService::class);

    $result = $service->invite($client, [
        'name' => 'Ignored Name',
        'email' => 'existing@client.test',
        'role' => ClientMembershipRole::Primary,
    ]);

    expect($result->createdNewUser)->toBeFalse()
        ->and($result->membership->user_id)->toBe($user->id)
        ->and(User::query()->where('email', 'existing@client.test')->count())->toBe(1);
});

it('rejects duplicate client memberships', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create(['email' => 'existing@client.test']);
    ClientMembership::factory()->for($client)->for($user)->create();

    $service = app(ClientMemberInviteService::class);

    expect(fn () => $service->invite($client, [
        'name' => 'Existing User',
        'email' => 'existing@client.test',
        'role' => ClientMembershipRole::Primary,
    ]))->toThrow(ValidationException::class);
});

<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;
use Laravel\Sanctum\Sanctum;

it('denies portal access without client membership', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson($this->apiUrl('portal/client'))
        ->assertForbidden();
});

it('shows the active client organization', function () {
    $portal = $this->createClientPortalUser();

    $this->actingAsClient($portal['user'], $portal['client'])
        ->getJson($this->apiUrl('portal/client'))
        ->assertOk()
        ->assertJsonPath('id', $portal['client']->id)
        ->assertJsonPath('name', $portal['client']->name);
});

it('denies access to a client the user does not belong to', function () {
    $portal = $this->createClientPortalUser();
    $foreignClient = Client::factory()->create();

    Sanctum::actingAs($portal['user']);

    $this->withHeader('X-Client-Id', (string) $foreignClient->id)
        ->getJson($this->apiUrl('portal/client'))
        ->assertForbidden();
});

it('denies access when the client organization is archived', function () {
    $portal = $this->createClientPortalUser();
    $portal['client']->update(['status' => ClientStatus::Archived]);

    $this->actingAsClient($portal['user'], $portal['client'])
        ->getJson($this->apiUrl('portal/client'))
        ->assertForbidden();
});

it('auto selects client context when user belongs to one organization', function () {
    $portal = $this->createClientPortalUser();

    Sanctum::actingAs($portal['user']);

    $this->getJson($this->apiUrl('portal/client'))
        ->assertOk()
        ->assertJsonPath('id', $portal['client']->id);
});

it('requires client header when user belongs to multiple client organizations', function () {
    $user = User::factory()->create();
    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    ClientMembership::factory()->for($firstClient)->for($user)->create();
    ClientMembership::factory()->for($secondClient)->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson($this->apiUrl('portal/client'))
        ->assertForbidden();
});

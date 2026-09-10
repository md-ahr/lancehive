<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to project routes', function () {
    $this->getJson($this->apiUrl('projects'))
        ->assertUnauthorized();
});

it('denies access without valid tenant context', function () {
    Sanctum::actingAs(User::factory()->freelancer()->create());

    $this->getJson($this->apiUrl('projects'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('denies non members from accessing another workspace projects', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();

    Sanctum::actingAs($workspaceA['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspaceB['freelancer']->id)
        ->getJson($this->apiUrl('projects'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

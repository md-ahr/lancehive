<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\FreelancerMembership;
use Laravel\Sanctum\Sanctum;

it('resolves workspace settings from active tenant context', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $user = User::factory()->create();

    FreelancerMembership::factory()->for($workspaceA['freelancer'])->for($user)->owner()->create();
    FreelancerMembership::factory()->for($workspaceB['freelancer'])->for($user)->owner()->create();

    $workspaceA['freelancer']->update(['business_name' => 'Workspace A']);
    $workspaceB['freelancer']->update(['business_name' => 'Workspace B']);

    Sanctum::actingAs($user);

    $this->withFreelancerContext($workspaceA['freelancer'])
        ->getJson($this->apiUrl('workspace/settings'))
        ->assertOk()
        ->assertJsonPath('business_name', 'Workspace A');

    $this->withFreelancerContext($workspaceB['freelancer'])
        ->getJson($this->apiUrl('workspace/settings'))
        ->assertOk()
        ->assertJsonPath('business_name', 'Workspace B');
});

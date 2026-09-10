<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Project;

it('lists projects for the active client organization', function () {
    $portal = $this->createClientPortalUser();
    $visibleProject = Project::factory()->for($portal['client'])->for($portal['client']->freelancer)->create([
        'name' => 'Visible Project',
    ]);
    $foreignProject = Project::factory()->create(['name' => 'Foreign Project']);

    $response = $this->actingAsClient($portal['user'], $portal['client'])
        ->getJson($this->apiUrl('portal/projects'))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id'))
        ->toContain($visibleProject->id)
        ->not->toContain($foreignProject->id);
});

it('denies unauthenticated access to portal projects', function () {
    $this->getJson($this->apiUrl('portal/projects'))
        ->assertUnauthorized();
});

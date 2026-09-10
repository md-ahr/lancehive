<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Http\Resources\ProjectResource;
use App\Features\Delivery\Models\Project;

it('serializes project resource with expected keys and decimal hourly rate', function () {
    $project = Project::factory()->create([
        'name' => 'Website Redesign',
        'hourly_rate' => '1500.00',
        'currency' => 'BDT',
        'status' => ProjectStatus::Active,
        'deadline' => '2026-06-30',
    ]);

    $payload = (new ProjectResource($project))->resolve();

    expect($payload)->toMatchArray([
        'id' => $project->id,
        'client_id' => $project->client_id,
        'name' => 'Website Redesign',
        'hourly_rate' => '1500.00',
        'currency' => 'BDT',
        'status' => 'active',
        'deadline' => '2026-06-30',
    ])
        ->and($payload)->toHaveKeys(['created_at', 'updated_at']);
});

it('serializes nullable deadline as null', function () {
    $project = Project::factory()->create(['deadline' => null]);

    $payload = (new ProjectResource($project))->resolve();

    expect($payload['deadline'])->toBeNull();
});

it('formats hourly rate as a two-decimal string', function () {
    $project = Project::factory()->create(['hourly_rate' => 500]);

    $payload = (new ProjectResource($project))->resolve();

    expect($payload['hourly_rate'])->toBe('500.00');
});

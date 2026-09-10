<?php

declare(strict_types=1);

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Project;

it('exposes all project status values', function () {
    expect(ProjectStatus::values())->toBe(['active', 'on_hold', 'completed']);
});

it('round trips project status through the model cast', function () {
    $project = Project::factory()->onHold()->create();

    expect($project->status)->toBe(ProjectStatus::OnHold)
        ->and($project->getAttributes()['status'])->toBe('on_hold');
});

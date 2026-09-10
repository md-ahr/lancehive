<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Policies\TaskPolicy;
use App\Features\Tenancy\Models\Freelancer;

it('allows members to manage tasks on tenant projects', function () {
    $workspace = test()->createTenantWorkspace();
    test()->setTenantContext($workspace['freelancer']);
    $project = Project::factory()->for($workspace['freelancer'])->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    $policy = new TaskPolicy;

    expect($policy->create($workspace['user'], $project))->toBeTrue()
        ->and($policy->update($workspace['user'], $task))->toBeTrue();
});

it('denies creating tasks on foreign projects', function () {
    $workspace = test()->createTenantWorkspace();
    $foreignProject = Project::factory()->for(Freelancer::factory()->active()->create())->create();

    test()->setTenantContext($workspace['freelancer']);

    $policy = new TaskPolicy;

    expect($policy->create($workspace['user'], $foreignProject))->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('belongs to project and has time logs', function () {
    $task = Task::factory()->create();
    $project = Project::withoutGlobalScopes()->findOrFail($task->project_id);
    test()->setTenantContext($project->freelancer_id);

    expect($task->project)->not->toBeNull()
        ->and($task->project->tasks->contains($task))->toBeTrue()
        ->and($task->timeLogs())->toBeInstanceOf(HasMany::class);
});

it('uses soft deletes', function () {
    $task = Task::factory()->create();
    $project = Project::withoutGlobalScopes()->findOrFail($task->project_id);
    test()->setTenantContext($project->freelancer_id);
    $task->delete();

    expect($task->trashed())->toBeTrue()
        ->and(Task::query()->count())->toBe(0)
        ->and(Task::withTrashed()->count())->toBe(1);
});

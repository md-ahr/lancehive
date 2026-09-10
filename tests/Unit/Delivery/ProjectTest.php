<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Project;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('requires hourly_rate when creating a project', function () {
    $project = Project::factory()->make(['hourly_rate' => null]);

    expect(fn () => $project->save())->toThrow(InvalidArgumentException::class, 'hourly_rate is required');
});

it('relates to client freelancer tasks and invoices', function () {
    $project = Project::factory()->create();

    expect($project->client)->not->toBeNull()
        ->and($project->freelancer)->not->toBeNull()
        ->and($project->tasks())->toBeInstanceOf(HasMany::class)
        ->and($project->clientInvoices())->toBeInstanceOf(HasMany::class);
});

it('supports factory states', function () {
    expect(Project::factory()->completed()->create()->status->value)->toBe('completed');
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;

it('returns cursor-paginated time logs sorted by logged_at descending', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    $otherTask = Task::factory()->for($project)->create();

    TimeLog::factory()->for($task)->create([
        'logged_at' => '2026-03-01 09:00:00',
        'hours' => '1.00',
    ]);
    TimeLog::factory()->for($task)->create([
        'logged_at' => '2026-03-03 09:00:00',
        'hours' => '3.00',
    ]);
    TimeLog::factory()->for($task)->create([
        'logged_at' => '2026-03-02 09:00:00',
        'hours' => '2.00',
    ]);
    TimeLog::factory()->for($otherTask)->create(['hours' => '9.00']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl("tasks/{$task->id}/time-logs?per_page=2"))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'task_id',
                    'user_id',
                    'hours',
                    'description',
                    'logged_at',
                    'client_invoice_item_id',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('data.0.hours', '3.00')
        ->assertJsonPath('data.1.hours', '2.00')
        ->assertJsonMissing(['hours' => '9.00']);
});

it('returns not found when listing time logs for another tenant task', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspaceB['freelancer'])->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->getJson($this->apiUrl("tasks/{$task->id}/time-logs"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;

it('runs a workspace time logs report', function () {
    $workspace = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    TimeLog::factory()->for($task)->create(['hours' => '3.50']);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('reports/run'), [
            'report_type' => 'time_logs',
            'filters' => [],
        ])
        ->assertOk()
        ->assertJsonPath('report_type', 'time_logs')
        ->assertJsonPath('summary.total_hours', '3.50')
        ->assertJsonStructure(['summary', 'preview']);
});

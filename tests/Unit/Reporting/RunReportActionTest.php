<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Reporting\Actions\RunReportAction;
use App\Features\Reporting\Enums\ReportType;
use Illuminate\Http\Request;

it('returns summary and preview for time logs report', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    $client = Client::factory()->for($workspace['freelancer'])->create();
    $project = Project::factory()->for($client)->for($workspace['freelancer'])->create();
    $task = Task::factory()->for($project)->create();
    TimeLog::factory()->for($task)->create(['hours' => '4.00']);

    $result = app(RunReportAction::class)->execute(
        ReportType::TimeLogs,
        [],
        Request::create('/api/v1/reports/run', 'POST'),
        tenantScoped: true,
        freelancer: $workspace['freelancer'],
    );

    expect($result['summary']['total_hours'])->toBe('4.00')
        ->and($result['preview']['data'])->toHaveCount(1);
});

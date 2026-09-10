<?php

declare(strict_types=1);

namespace App\Features\Reporting\Jobs;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Notifications\ReportReadyNotification;
use App\Features\Reporting\Services\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $exportId) {}

    public function handle(ReportExportService $exportService): void
    {
        $export = ReportExport::query()->find($this->exportId);

        if ($export === null) {
            return;
        }

        $exportService->processExport($export);

        $export->refresh();

        if ($export->status->value !== 'completed') {
            return;
        }

        $user = User::query()->find($export->requested_by_user_id);

        if ($user !== null) {
            $user->notify(new ReportReadyNotification($export));
        }
    }
}

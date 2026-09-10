<?php

declare(strict_types=1);

namespace App\Features\Reporting\Console;

use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Services\ReportExportService;
use Illuminate\Console\Command;

final class PurgeExpiredReportExportsCommand extends Command
{
    protected $signature = 'reports:purge-expired-exports';

    protected $description = 'Delete expired report exports and their files from disk';

    public function handle(ReportExportService $exportService): int
    {
        $expired = ReportExport::query()
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expired as $export) {
            $exportService->deleteExportFile($export);
            $export->delete();
            $count++;
        }

        $this->info("Purged {$count} expired report export(s).");

        return self::SUCCESS;
    }
}

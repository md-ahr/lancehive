<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Concerns;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Tenancy\TenantContext;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Reporting\Models\SavedReport;

trait ResolvesScopedReports
{
    protected function resolveWorkspaceSavedReport(int|string $id): SavedReport
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $report = SavedReport::query()
            ->forWorkspace($freelancerId)
            ->whereKey($id)
            ->first();

        if ($report === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        return $report;
    }

    protected function resolvePlatformSavedReport(int|string $id): SavedReport
    {
        $report = SavedReport::query()
            ->forPlatform()
            ->whereKey($id)
            ->first();

        if ($report === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        return $report;
    }

    protected function resolveWorkspaceReportExport(int|string $id): ReportExport
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $export = ReportExport::query()
            ->forWorkspace($freelancerId)
            ->whereKey($id)
            ->first();

        if ($export === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        return $export;
    }

    protected function resolvePlatformReportExport(int|string $id): ReportExport
    {
        $export = ReportExport::query()
            ->forPlatform()
            ->whereKey($id)
            ->first();

        if ($export === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        return $export;
    }
}

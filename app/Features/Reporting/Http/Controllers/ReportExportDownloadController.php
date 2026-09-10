<?php

declare(strict_types=1);

namespace App\Features\Reporting\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Models\ReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExportDownloadController extends Controller
{
    public function download(Request $request, int $export): StreamedResponse
    {
        $reportExport = ReportExport::query()->whereKey($export)->first();

        if ($reportExport === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        if ($reportExport->isExpired()) {
            throw new ApiException(ApiErrorCode::ExportExpired);
        }

        if ($reportExport->status !== ExportStatus::Completed || $reportExport->file_path === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        if (! $request->hasValidSignature()) {
            throw new ApiException(ApiErrorCode::Forbidden);
        }

        return Storage::disk('report-exports')->download($reportExport->file_path);
    }
}

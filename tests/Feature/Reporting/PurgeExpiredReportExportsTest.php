<?php

declare(strict_types=1);

use App\Features\Reporting\Models\ReportExport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('purges expired exports and deletes files', function () {
    Storage::fake('report-exports');
    $workspace = $this->createTenantWorkspace();

    $export = ReportExport::factory()
        ->for($workspace['freelancer'])
        ->completed()
        ->expired()
        ->create(['file_path' => 'exports/old.csv']);

    Storage::disk('report-exports')->put('exports/old.csv', 'data');

    Artisan::call('reports:purge-expired-exports');

    expect(ReportExport::query()->whereKey($export->id)->exists())->toBeFalse()
        ->and(Storage::disk('report-exports')->exists('exports/old.csv'))->toBeFalse();
});

it('deletes row when file is already missing on disk', function () {
    Storage::fake('report-exports');
    $workspace = $this->createTenantWorkspace();

    $export = ReportExport::factory()
        ->for($workspace['freelancer'])
        ->completed()
        ->expired()
        ->create(['file_path' => 'exports/missing.csv']);

    Artisan::call('reports:purge-expired-exports');

    expect(ReportExport::query()->whereKey($export->id)->exists())->toBeFalse();
});

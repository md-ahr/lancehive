<?php

declare(strict_types=1);

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\Delivery\Models\Client;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Services\ReportFilterValidator;
use Illuminate\Validation\ValidationException;

it('strips unknown filter keys', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    $normalized = app(ReportFilterValidator::class)->validateAndNormalize(
        ReportType::TimeLogs,
        ['from' => '2026-01-01', 'unknown' => 'x'],
    );

    expect($normalized)->toBe(['from' => '2026-01-01']);
});

it('rejects from date after to date', function () {
    $workspace = $this->createTenantWorkspace();
    $this->setTenantContext($workspace['freelancer']);

    app(ReportFilterValidator::class)->validateAndNormalize(
        ReportType::TimeLogs,
        ['from' => '2026-02-01', 'to' => '2026-01-01'],
    );
})->throws(ValidationException::class);

it('returns not found for cross tenant client id', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $client = Client::factory()->for($workspaceB['freelancer'])->create();

    $this->setTenantContext($workspaceA['freelancer']);

    try {
        app(ReportFilterValidator::class)->validateAndNormalize(
            ReportType::TimeLogs,
            ['client_id' => $client->id],
        );
        expect(false)->toBeTrue();
    } catch (ApiException $exception) {
        expect($exception->errorCode)->toBe(ApiErrorCode::NotFound);
    }
});

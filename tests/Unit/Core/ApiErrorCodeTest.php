<?php

declare(strict_types=1);

use App\Core\Http\Enums\ApiErrorCode;

it('maps error codes to correct http status', function (ApiErrorCode $code, int $status) {
    expect($code->httpStatus())->toBe($status);
})->with([
    'unauthenticated' => [ApiErrorCode::Unauthenticated, 401],
    'forbidden' => [ApiErrorCode::Forbidden, 403],
    'workspace_read_only' => [ApiErrorCode::WorkspaceReadOnly, 403],
    'super_admin_required' => [ApiErrorCode::SuperAdminRequired, 403],
    'not_found' => [ApiErrorCode::NotFound, 404],
    'plan_limit_exceeded' => [ApiErrorCode::PlanLimitExceeded, 422],
    'invoice_not_editable' => [ApiErrorCode::InvoiceNotEditable, 422],
    'too_many_requests' => [ApiErrorCode::TooManyRequests, 429],
]);

it('uses snake_case string values for api error codes', function () {
    expect(ApiErrorCode::WorkspaceReadOnly->value)->toBe('workspace_read_only')
        ->and(ApiErrorCode::PlanLimitExceeded->value)->toBe('plan_limit_exceeded');
});

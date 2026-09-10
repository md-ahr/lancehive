<?php

declare(strict_types=1);

namespace App\Core\Http\Enums;

enum ApiErrorCode: string
{
    case Unauthenticated = 'unauthenticated';
    case Forbidden = 'forbidden';
    case WorkspaceReadOnly = 'workspace_read_only';
    case SuperAdminRequired = 'super_admin_required';
    case NotFound = 'not_found';
    case PlanLimitExceeded = 'plan_limit_exceeded';
    case InvoiceNotEditable = 'invoice_not_editable';
    case TooManyRequests = 'too_many_requests';

    public function httpStatus(): int
    {
        return match ($this) {
            self::Unauthenticated => 401,
            self::Forbidden, self::WorkspaceReadOnly, self::SuperAdminRequired => 403,
            self::NotFound => 404,
            self::PlanLimitExceeded, self::InvoiceNotEditable => 422,
            self::TooManyRequests => 429,
        };
    }
}

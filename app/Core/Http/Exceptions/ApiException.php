<?php

declare(strict_types=1);

namespace App\Core\Http\Exceptions;

use App\Core\Http\Enums\ApiErrorCode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiException extends Exception
{
    public function __construct(
        public readonly ApiErrorCode $errorCode,
        string $message = '',
        ?Exception $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : $this->defaultMessage(), 0, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode->value,
        ], $this->errorCode->httpStatus());
    }

    private function defaultMessage(): string
    {
        return match ($this->errorCode) {
            ApiErrorCode::Unauthenticated => 'Unauthenticated.',
            ApiErrorCode::Forbidden => 'This action is unauthorized.',
            ApiErrorCode::WorkspaceReadOnly => 'This workspace is read-only. Renew your subscription to make changes.',
            ApiErrorCode::SuperAdminRequired => 'Super-admin access required.',
            ApiErrorCode::NotFound => 'Resource not found.',
            ApiErrorCode::PlanLimitExceeded => 'Plan limit reached.',
            ApiErrorCode::InvoiceNotEditable => 'This invoice cannot be modified.',
            ApiErrorCode::TooManyRequests => 'Too many requests.',
            ApiErrorCode::ExportExpired => 'This report export has expired.',
        };
    }
}

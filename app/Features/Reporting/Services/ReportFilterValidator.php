<?php

declare(strict_types=1);

namespace App\Features\Reporting\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Tenancy\Enums\FreelancerStatus;
use Illuminate\Validation\ValidationException;

final class ReportFilterValidator
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function validateAndNormalize(ReportType $reportType, array $filters, bool $tenantScoped = true): array
    {
        $allowedKeys = $this->allowedKeysFor($reportType);
        $normalized = [];

        foreach ($filters as $key => $value) {
            if (! in_array($key, $allowedKeys, true)) {
                continue;
            }

            $normalized[$key] = $value;
        }

        $this->validateDateOrder($normalized);
        $this->validateFilterValues($reportType, $normalized);

        if ($tenantScoped) {
            $this->assertTenantOwnership($normalized);
        } else {
            $this->assertPlatformFilterValues($normalized);
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function allowedKeysFor(ReportType $reportType): array
    {
        return match ($reportType) {
            ReportType::TimeLogs => ['from', 'to', 'client_id', 'project_id', 'user_id'],
            ReportType::UnbilledWork => ['from', 'to', 'client_id', 'project_id'],
            ReportType::ClientInvoices => ['from', 'to', 'client_id', 'project_id', 'status'],
            ReportType::FreelancerList => ['status', 'plan_id'],
            ReportType::SubscriptionRevenue => ['from', 'to', 'status', 'plan_id'],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function validateDateOrder(array $filters): void
    {
        if (! isset($filters['from'], $filters['to'])) {
            return;
        }

        if ($filters['from'] > $filters['to']) {
            throw ValidationException::withMessages([
                'filters.to' => ['The to date must be after or equal to the from date.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function validateFilterValues(ReportType $reportType, array $filters): void
    {
        $errors = [];

        if (isset($filters['from']) && ! $this->isValidDate($filters['from'])) {
            $errors['filters.from'] = ['The from date must be a valid date (Y-m-d).'];
        }

        if (isset($filters['to']) && ! $this->isValidDate($filters['to'])) {
            $errors['filters.to'] = ['The to date must be a valid date (Y-m-d).'];
        }

        if (isset($filters['status'])) {
            $validStatuses = match ($reportType) {
                ReportType::ClientInvoices => ClientInvoiceStatus::values(),
                ReportType::FreelancerList => FreelancerStatus::values(),
                ReportType::SubscriptionRevenue => SubscriptionChargeStatus::values(),
                default => [],
            };

            if (! in_array($filters['status'], $validStatuses, true)) {
                $errors['filters.status'] = ['The selected status is invalid.'];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function assertTenantOwnership(array $filters): void
    {
        if (isset($filters['client_id'])) {
            $exists = Client::query()->whereKey($filters['client_id'])->exists();

            if (! $exists) {
                throw new ApiException(ApiErrorCode::NotFound);
            }
        }

        if (isset($filters['project_id'])) {
            $exists = Project::query()->whereKey($filters['project_id'])->exists();

            if (! $exists) {
                throw new ApiException(ApiErrorCode::NotFound);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function assertPlatformFilterValues(array $filters): void
    {
        if (isset($filters['plan_id'])) {
            $exists = Plan::query()->whereKey($filters['plan_id'])->exists();

            if (! $exists) {
                throw new ApiException(ApiErrorCode::NotFound);
            }
        }

        if (isset($filters['status']) && ! in_array($filters['status'], SubscriptionStatus::values(), true)
            && ! in_array($filters['status'], SubscriptionChargeStatus::values(), true)
            && ! in_array($filters['status'], FreelancerStatus::values(), true)) {
            throw ValidationException::withMessages([
                'filters.status' => ['The selected status is invalid.'],
            ]);
        }
    }

    private function isValidDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}

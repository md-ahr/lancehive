<?php

declare(strict_types=1);

use App\Features\Reporting\Enums\ReportScope;
use App\Features\Reporting\Enums\ReportType;

it('maps each report type to the correct scope', function () {
    expect(ReportType::WorkspaceOverview->scope())->toBe(ReportScope::Workspace)
        ->and(ReportType::TimeLogs->scope())->toBe(ReportScope::Workspace)
        ->and(ReportType::UnbilledWork->scope())->toBe(ReportScope::Workspace)
        ->and(ReportType::ClientInvoices->scope())->toBe(ReportScope::Workspace)
        ->and(ReportType::PlatformOverview->scope())->toBe(ReportScope::Platform)
        ->and(ReportType::FreelancerList->scope())->toBe(ReportScope::Platform)
        ->and(ReportType::SubscriptionRevenue->scope())->toBe(ReportScope::Platform);
});

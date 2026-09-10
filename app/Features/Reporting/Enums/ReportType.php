<?php

declare(strict_types=1);

namespace App\Features\Reporting\Enums;

enum ReportType: string
{
    case WorkspaceOverview = 'workspace_overview';
    case TimeLogs = 'time_logs';
    case UnbilledWork = 'unbilled_work';
    case ClientInvoices = 'client_invoices';
    case PlatformOverview = 'platform_overview';
    case FreelancerList = 'freelancer_list';
    case SubscriptionRevenue = 'subscription_revenue';

    public function scope(): ReportScope
    {
        return match ($this) {
            self::WorkspaceOverview,
            self::TimeLogs,
            self::UnbilledWork,
            self::ClientInvoices => ReportScope::Workspace,
            self::PlatformOverview,
            self::FreelancerList,
            self::SubscriptionRevenue => ReportScope::Platform,
        };
    }

    public function isRunnable(): bool
    {
        return ! in_array($this, [self::WorkspaceOverview, self::PlatformOverview], true);
    }

    /**
     * @return list<string>
     */
    public static function workspaceRunnableValues(): array
    {
        return [
            self::TimeLogs->value,
            self::UnbilledWork->value,
            self::ClientInvoices->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function platformRunnableValues(): array
    {
        return [
            self::FreelancerList->value,
            self::SubscriptionRevenue->value,
        ];
    }
}

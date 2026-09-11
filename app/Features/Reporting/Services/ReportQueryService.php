<?php

declare(strict_types=1);

namespace App\Features\Reporting\Services;

use App\Core\Tenancy\TenantContext;
use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\TimeLog;
use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use App\Features\Reporting\Cache\PlatformStatsCache;
use App\Features\Reporting\Cache\WorkspaceStatsCache;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ReportQueryService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly WorkspaceStatsCache $workspaceStatsCache,
        private readonly PlatformStatsCache $platformStatsCache,
    ) {}

    /**
     * @return array{
     *     active_clients: int,
     *     active_projects: int,
     *     hours_this_month: string,
     *     unbilled_hours: string,
     *     outstanding_invoice_total: string
     * }
     */
    public function workspaceOverview(): array
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            return $this->computeWorkspaceOverview();
        }

        return $this->workspaceStatsCache->remember(
            $freelancerId,
            fn (): array => $this->computeWorkspaceOverview(),
        );
    }

    /**
     * @return array{
     *     active_clients: int,
     *     active_projects: int,
     *     hours_this_month: string,
     *     unbilled_hours: string,
     *     outstanding_invoice_total: string
     * }
     */
    private function computeWorkspaceOverview(): array
    {
        $activeClients = Client::query()
            ->where('status', ClientStatus::Active)
            ->count();

        $activeProjects = Project::query()
            ->where('status', ProjectStatus::Active)
            ->count();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $timeAggregates = TimeLog::query()
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN logged_at >= ? AND logged_at <= ? THEN hours ELSE 0 END), 0) as hours_this_month', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN client_invoice_item_id IS NULL THEN hours ELSE 0 END), 0) as unbilled_hours')
            ->first();

        $outstandingTotal = $this->outstandingInvoiceTotal();

        return [
            'active_clients' => $activeClients,
            'active_projects' => $activeProjects,
            'hours_this_month' => $this->formatHours((float) ($timeAggregates->hours_this_month ?? 0)),
            'unbilled_hours' => $this->formatHours((float) ($timeAggregates->unbilled_hours ?? 0)),
            'outstanding_invoice_total' => $this->formatMoney($outstandingTotal),
        ];
    }

    /**
     * @return array{
     *     freelancers_by_status: array<string, int>,
     *     subscriptions_by_plan: list<array{plan_id: int, plan_name: string, count: int}>,
     *     subscriptions_by_status: array<string, int>,
     *     trials_ending_soon: int
     * }
     */
    public function platformOverview(): array
    {
        return $this->platformStatsCache->remember(
            fn (): array => $this->computePlatformOverview(),
        );
    }

    /**
     * @return array{
     *     freelancers_by_status: array<string, int>,
     *     subscriptions_by_plan: list<array{plan_id: int, plan_name: string, count: int}>,
     *     subscriptions_by_status: array<string, int>,
     *     trials_ending_soon: int
     * }
     */
    private function computePlatformOverview(): array
    {
        $freelancersByStatus = [];
        foreach (FreelancerStatus::cases() as $status) {
            $freelancersByStatus[$status->value] = Freelancer::query()
                ->where('status', $status)
                ->count();
        }

        $subscriptionsByPlan = Subscription::query()
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->get()
            ->map(function ($row): array {
                $plan = Plan::query()->find($row->plan_id);

                return [
                    'plan_id' => (int) $row->plan_id,
                    'plan_name' => $plan?->name ?? 'Unknown',
                    'count' => (int) $row->count,
                ];
            })
            ->values()
            ->all();

        $subscriptionsByStatus = [];
        foreach (SubscriptionStatus::cases() as $status) {
            $subscriptionsByStatus[$status->value] = Subscription::query()
                ->where('status', $status)
                ->count();
        }

        $trialsEndingSoon = Subscription::query()
            ->where('status', SubscriptionStatus::Trialing)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();

        return [
            'freelancers_by_status' => $freelancersByStatus,
            'subscriptions_by_plan' => $subscriptionsByPlan,
            'subscriptions_by_status' => $subscriptionsByStatus,
            'trials_ending_soon' => $trialsEndingSoon,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_hours: string, billed_hours: string, unbilled_hours: string}
     */
    public function timeLogsSummary(array $filters): array
    {
        $aggregates = $this->timeLogsQuery($filters)
            ->toBase()
            ->selectRaw('COALESCE(SUM(hours), 0) as total_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN client_invoice_item_id IS NOT NULL THEN hours ELSE 0 END), 0) as billed_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN client_invoice_item_id IS NULL THEN hours ELSE 0 END), 0) as unbilled_hours')
            ->first();

        return [
            'total_hours' => $this->formatHours((float) ($aggregates->total_hours ?? 0)),
            'billed_hours' => $this->formatHours((float) ($aggregates->billed_hours ?? 0)),
            'unbilled_hours' => $this->formatHours((float) ($aggregates->unbilled_hours ?? 0)),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function timeLogsQuery(array $filters): Builder
    {
        $query = TimeLog::query();

        $this->applyTimeLogFilters($query, $filters);

        return $query->orderByDesc('logged_at')->orderByDesc('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_unbilled_hours: string, estimated_revenue: string, currency: string}
     */
    public function unbilledWorkSummary(array $filters, string $defaultCurrency = 'BDT'): array
    {
        $query = $this->unbilledWorkQuery($filters);

        $aggregates = $query->toBase()
            ->selectRaw('COALESCE(SUM(time_logs.hours), 0) as total_unbilled_hours')
            ->selectRaw('COALESCE(SUM(time_logs.hours * projects.hourly_rate), 0) as estimated_revenue')
            ->first();

        return [
            'total_unbilled_hours' => $this->formatHours((float) ($aggregates->total_unbilled_hours ?? 0)),
            'estimated_revenue' => $this->formatMoney((float) ($aggregates->estimated_revenue ?? 0)),
            'currency' => $defaultCurrency,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function unbilledWorkQuery(array $filters): Builder
    {
        $query = TimeLog::query()
            ->select('time_logs.*')
            ->join('tasks', 'tasks.id', '=', 'time_logs.task_id')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->whereNull('time_logs.client_invoice_item_id');

        $this->applyTimeLogFilters($query, $filters, tablePrefix: 'time_logs.');

        return $query->orderByDesc('time_logs.logged_at')->orderByDesc('time_logs.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count_by_status: array<string, int>, total_by_status: array<string, string>, outstanding_total: string}
     */
    public function clientInvoicesSummary(array $filters): array
    {
        $query = $this->clientInvoicesQuery($filters);

        $countByStatus = [];
        $totalByStatus = [];

        foreach (ClientInvoiceStatus::cases() as $status) {
            $countByStatus[$status->value] = (clone $query)->where('status', $status)->count();
            $total = (clone $query)->where('status', $status)->sum('total');
            $totalByStatus[$status->value] = $this->formatMoney((float) $total);
        }

        $outstandingStatuses = [ClientInvoiceStatus::Sent, ClientInvoiceStatus::Overdue];
        $outstandingTotal = 0.0;

        $invoices = (clone $query)
            ->whereIn('status', $outstandingStatuses)
            ->withSum('payments', 'amount')
            ->get();

        foreach ($invoices as $invoice) {
            $paid = (float) ($invoice->payments_sum_amount ?? 0);
            $outstandingTotal += max(0, (float) $invoice->total - $paid);
        }

        return [
            'count_by_status' => $countByStatus,
            'total_by_status' => $totalByStatus,
            'outstanding_total' => $this->formatMoney($outstandingTotal),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function clientInvoicesQuery(array $filters): Builder
    {
        $query = ClientInvoice::query();

        if (isset($filters['from'])) {
            $query->whereDate('issued_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('issued_at', '<=', $filters['to']);
        }

        if (isset($filters['client_id'])) {
            $query->whereHas('project', fn (Builder $q): Builder => $q->where('client_id', $filters['client_id']));
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count_by_status: array<string, int>}
     */
    public function freelancerListSummary(array $filters): array
    {
        $countByStatus = [];

        foreach (FreelancerStatus::cases() as $status) {
            $query = $this->freelancerListQuery($filters);
            $countByStatus[$status->value] = (clone $query)
                ->where('freelancers.status', $status)
                ->count();
        }

        return ['count_by_status' => $countByStatus];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function freelancerListQuery(array $filters): Builder
    {
        $query = Freelancer::query()->with('subscription.plan');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['plan_id'])) {
            $query->whereHas('subscription', fn (Builder $q): Builder => $q->where('plan_id', $filters['plan_id']));
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{paid_total: string, currency: string, charge_count: int}
     */
    public function subscriptionRevenueSummary(array $filters): array
    {
        $query = $this->subscriptionChargesQuery($filters);

        $aggregates = $query->toBase()
            ->selectRaw('COALESCE(SUM(amount), 0) as paid_total')
            ->selectRaw('COUNT(*) as charge_count')
            ->first();

        $currency = $query->value('currency') ?? 'BDT';

        return [
            'paid_total' => $this->formatMoney((float) ($aggregates->paid_total ?? 0)),
            'currency' => is_string($currency) ? $currency : 'BDT',
            'charge_count' => (int) ($aggregates->charge_count ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function subscriptionChargesQuery(array $filters): Builder
    {
        $query = SubscriptionCharge::query()
            ->where('status', SubscriptionChargeStatus::Paid);

        if (isset($filters['from'])) {
            $query->whereDate('paid_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('paid_at', '<=', $filters['to']);
        }

        if (isset($filters['plan_id'])) {
            $query->whereHas('subscription', fn (Builder $q): Builder => $q->where('plan_id', $filters['plan_id']));
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('paid_at')->orderByDesc('id');
    }

    private function outstandingInvoiceTotal(): float
    {
        $total = 0.0;
        $statuses = [ClientInvoiceStatus::Sent, ClientInvoiceStatus::Overdue];

        $invoices = ClientInvoice::query()
            ->whereIn('status', $statuses)
            ->withSum('payments', 'amount')
            ->get();

        foreach ($invoices as $invoice) {
            $paid = (float) ($invoice->payments_sum_amount ?? 0);
            $total += max(0, (float) $invoice->total - $paid);
        }

        return $total;
    }

    /**
     * @param  Builder<TimeLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyTimeLogFilters(Builder $query, array $filters, string $tablePrefix = ''): void
    {
        $loggedAtColumn = $tablePrefix.'logged_at';
        $userIdColumn = $tablePrefix.'user_id';

        if (isset($filters['from'])) {
            $query->whereDate($loggedAtColumn, '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate($loggedAtColumn, '<=', $filters['to']);
        }

        if (isset($filters['user_id'])) {
            $query->where($userIdColumn, $filters['user_id']);
        }

        if (isset($filters['project_id'])) {
            $query->whereHas('task', fn (Builder $q): Builder => $q->where('project_id', $filters['project_id']));
        }

        if (isset($filters['client_id'])) {
            $query->whereHas('task.project', fn (Builder $q): Builder => $q->where('client_id', $filters['client_id']));
        }
    }

    private function formatHours(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\TimeLog;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Reporting\Observers\PlatformStatsObserver;
use App\Features\Reporting\Observers\WorkspaceStatsObserver;
use App\Features\Settings\Observers\FreelancerWorkspaceSettingsObserver;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\ServiceProvider;

final class CacheInvalidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $workspaceStatsObserver = $this->app->make(WorkspaceStatsObserver::class);
        $platformStatsObserver = $this->app->make(PlatformStatsObserver::class);

        Client::saved(fn (Client $client) => $workspaceStatsObserver->savedClient($client));
        Client::deleted(fn (Client $client) => $workspaceStatsObserver->deletedClient($client));

        Project::saved(fn (Project $project) => $workspaceStatsObserver->savedProject($project));
        Project::deleted(fn (Project $project) => $workspaceStatsObserver->deletedProject($project));

        TimeLog::saved(fn (TimeLog $timeLog) => $workspaceStatsObserver->savedTimeLog($timeLog));
        TimeLog::deleted(fn (TimeLog $timeLog) => $workspaceStatsObserver->deletedTimeLog($timeLog));

        ClientInvoice::saved(fn (ClientInvoice $invoice) => $workspaceStatsObserver->savedClientInvoice($invoice));
        ClientInvoice::deleted(fn (ClientInvoice $invoice) => $workspaceStatsObserver->deletedClientInvoice($invoice));

        ClientInvoicePayment::saved(fn (ClientInvoicePayment $payment) => $workspaceStatsObserver->savedClientInvoicePayment($payment));
        ClientInvoicePayment::deleted(fn (ClientInvoicePayment $payment) => $workspaceStatsObserver->deletedClientInvoicePayment($payment));

        Freelancer::saved(fn (Freelancer $freelancer) => $platformStatsObserver->savedFreelancer($freelancer));
        Freelancer::deleted(fn (Freelancer $freelancer) => $platformStatsObserver->deletedFreelancer($freelancer));

        Subscription::saved(fn (Subscription $subscription) => $platformStatsObserver->savedSubscription($subscription));
        Subscription::deleted(fn (Subscription $subscription) => $platformStatsObserver->deletedSubscription($subscription));

        Plan::saved(fn (Plan $plan) => $platformStatsObserver->savedPlan($plan));

        Freelancer::observe(FreelancerWorkspaceSettingsObserver::class);
    }
}

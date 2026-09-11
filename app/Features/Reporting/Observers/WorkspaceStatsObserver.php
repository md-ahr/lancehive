<?php

declare(strict_types=1);

namespace App\Features\Reporting\Observers;

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Reporting\Services\InvalidateCachedSnapshots;

final class WorkspaceStatsObserver
{
    public function __construct(private readonly InvalidateCachedSnapshots $invalidator) {}

    public function savedClient(Client $client): void
    {
        $this->forgetForFreelancer($client->freelancer_id);
    }

    public function deletedClient(Client $client): void
    {
        $this->forgetForFreelancer($client->freelancer_id);
    }

    public function savedProject(Project $project): void
    {
        $this->forgetForFreelancer($project->freelancer_id);
    }

    public function deletedProject(Project $project): void
    {
        $this->forgetForFreelancer($project->freelancer_id);
    }

    public function savedTimeLog(TimeLog $timeLog): void
    {
        $freelancerId = $this->resolveFreelancerIdFromTimeLog($timeLog);

        if ($freelancerId !== null) {
            $this->invalidator->workspace($freelancerId);
        }
    }

    public function deletedTimeLog(TimeLog $timeLog): void
    {
        $this->savedTimeLog($timeLog);
    }

    public function savedClientInvoice(ClientInvoice $invoice): void
    {
        $this->forgetForFreelancer($invoice->freelancer_id);
    }

    public function deletedClientInvoice(ClientInvoice $invoice): void
    {
        $this->forgetForFreelancer($invoice->freelancer_id);
    }

    public function savedClientInvoicePayment(ClientInvoicePayment $payment): void
    {
        $freelancerId = $payment->clientInvoice?->freelancer_id
            ?? $payment->clientInvoice()->value('freelancer_id');

        $this->forgetForFreelancer(is_numeric($freelancerId) ? (int) $freelancerId : null);
    }

    public function deletedClientInvoicePayment(ClientInvoicePayment $payment): void
    {
        $this->savedClientInvoicePayment($payment);
    }

    private function forgetForFreelancer(?int $freelancerId): void
    {
        if ($freelancerId !== null) {
            $this->invalidator->workspace($freelancerId);
        }
    }

    private function resolveFreelancerIdFromTimeLog(TimeLog $timeLog): ?int
    {
        if ($timeLog->relationLoaded('task')) {
            $project = $timeLog->task?->project;

            return $project?->freelancer_id;
        }

        $freelancerId = $timeLog->task()
            ->with('project:id,freelancer_id')
            ->first()
            ?->project
            ?->freelancer_id;

        return is_numeric($freelancerId) ? (int) $freelancerId : null;
    }
}

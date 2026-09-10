<?php

declare(strict_types=1);

use App\Core\Tenancy\TenantContext;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Models\Freelancer;

it('scopes tasks to the active tenant via project', function () {
    $freelancerA = Freelancer::factory()->active()->create();
    $freelancerB = Freelancer::factory()->active()->create();

    Task::factory()->create(['project_id' => Project::factory()->for($freelancerA)->create()->id]);
    Task::factory()->create(['project_id' => Project::factory()->for($freelancerB)->create()->id]);

    app(TenantContext::class)->setFreelancerId($freelancerA->id);

    expect(Task::query()->count())->toBe(1);
});

it('scopes time logs to the active tenant via task project chain', function () {
    $freelancerA = Freelancer::factory()->active()->create();
    $freelancerB = Freelancer::factory()->active()->create();
    $projectA = Project::factory()->for($freelancerA)->create();
    $projectB = Project::factory()->for($freelancerB)->create();
    $taskA = Task::factory()->create(['project_id' => $projectA->id]);
    $taskB = Task::factory()->create(['project_id' => $projectB->id]);

    TimeLog::factory()->create(['task_id' => $taskA->id]);
    TimeLog::factory()->create(['task_id' => $taskB->id]);

    app(TenantContext::class)->setFreelancerId($freelancerA->id);

    expect(TimeLog::query()->count())->toBe(1);
});

it('scopes invoice items and payments via client invoice freelancer', function () {
    $freelancerA = Freelancer::factory()->active()->create();
    $freelancerB = Freelancer::factory()->active()->create();

    $invoiceA = ClientInvoice::factory()->for($freelancerA)->create();
    $invoiceB = ClientInvoice::factory()->for($freelancerB)->create();

    ClientInvoiceItem::factory()->create(['client_invoice_id' => $invoiceA->id]);
    ClientInvoiceItem::factory()->create(['client_invoice_id' => $invoiceB->id]);
    ClientInvoicePayment::factory()->create(['client_invoice_id' => $invoiceA->id]);
    ClientInvoicePayment::factory()->create(['client_invoice_id' => $invoiceB->id]);

    app(TenantContext::class)->setFreelancerId($freelancerA->id);

    expect(ClientInvoiceItem::query()->count())->toBe(1)
        ->and(ClientInvoicePayment::query()->count())->toBe(1);
});

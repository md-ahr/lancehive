<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Policies\ClientInvoicePolicy;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('allows members to view invoices but not mutate them', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    test()->setTenantContext($workspace['freelancer']);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $policy = new ClientInvoicePolicy;

    expect($policy->view($workspace['user'], $invoice))->toBeTrue()
        ->and($policy->create($workspace['user']))->toBeFalse()
        ->and($policy->update($workspace['user'], $invoice))->toBeFalse()
        ->and($policy->delete($workspace['user'], $invoice))->toBeFalse();
});

it('allows owners and admins to manage invoices', function () {
    $workspace = test()->createTenantWorkspace(role: FreelancerMembershipRole::Owner);
    test()->setTenantContext($workspace['freelancer']);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $policy = new ClientInvoicePolicy;

    expect($policy->create($workspace['user']))->toBeTrue()
        ->and($policy->update($workspace['user'], $invoice))->toBeTrue()
        ->and($policy->delete($workspace['user'], $invoice))->toBeTrue();
});

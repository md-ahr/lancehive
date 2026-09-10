<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('soft deletes a draft invoice', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertOk()
        ->assertJsonPath('message', 'Client invoice deleted successfully.');

    expect(ClientInvoice::query()->find($invoice->id))->toBeNull()
        ->and(ClientInvoice::withTrashed()->find($invoice->id))->not->toBeNull();
});

it('returns invoice not editable when deleting a sent invoice', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->sent()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invoice_not_editable');
});

it('forbids members from deleting invoices', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->deleteJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('returns not found when deleting another tenant invoice', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("client-invoices/{$invoice->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

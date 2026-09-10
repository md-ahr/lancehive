<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;

it('adds an item and recalculates invoice totals', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create([
        'tax_rate' => 10,
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
    ]);

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/items"), [
            'description' => 'Development work',
            'quantity' => '2.00',
            'rate' => '1000.00',
        ])
        ->assertCreated()
        ->assertJsonPath('description', 'Development work')
        ->assertJsonPath('quantity', '2.00')
        ->assertJsonPath('rate', '1000.00')
        ->assertJsonPath('amount', '2000.00');

    $invoice->refresh();

    expect($invoice->subtotal)->toBe('2000.00')
        ->and($invoice->tax_amount)->toBe('200.00')
        ->and($invoice->total)->toBe('2200.00');
});

it('returns validation error when quantity is zero', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/items"), [
            'description' => 'Invalid item',
            'quantity' => '0.00',
            'rate' => '100.00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});

it('returns invoice not editable when adding items to a sent invoice', function () {
    $workspace = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->sent()->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/items"), [
            'description' => 'Too late',
            'quantity' => '1.00',
            'rate' => '100.00',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invoice_not_editable');
});

it('forbids members from adding invoice items', function () {
    $workspace = $this->createTenantWorkspace(role: FreelancerMembershipRole::Member);
    $invoice = ClientInvoice::factory()->for($workspace['freelancer'])->create();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/items"), [
            'description' => 'Blocked',
            'quantity' => '1.00',
            'rate' => '100.00',
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('returns not found when adding an item to another tenant invoice', function () {
    $workspaceA = $this->createTenantWorkspace();
    $workspaceB = $this->createTenantWorkspace();
    $invoice = ClientInvoice::factory()->for($workspaceB['freelancer'])->create();

    $this->actingAsTenant($workspaceA['user'], $workspaceA['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/items"), [
            'description' => 'Cross tenant',
            'quantity' => '1.00',
            'rate' => '100.00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

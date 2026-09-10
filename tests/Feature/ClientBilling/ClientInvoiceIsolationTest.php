<?php

declare(strict_types=1);

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;

beforeEach(function () {
    $this->workspaceA = $this->createTenantWorkspace();
    $this->workspaceB = $this->createTenantWorkspace();
    $this->clientB = Client::factory()->for($this->workspaceB['freelancer'])->create();
    $this->projectB = Project::factory()->for($this->clientB)->for($this->workspaceB['freelancer'])->create();
    $this->invoiceB = ClientInvoice::factory()->for($this->workspaceB['freelancer'])->create();
});

it('returns not found when showing another tenants invoice', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("client-invoices/{$this->invoiceB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when updating another tenants invoice', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->patchJson($this->apiUrl("client-invoices/{$this->invoiceB->id}"), [
            'notes' => 'Hijacked',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when deleting another tenants invoice', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->deleteJson($this->apiUrl("client-invoices/{$this->invoiceB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when adding an item to another tenants invoice', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$this->invoiceB->id}/items"), [
            'description' => 'Cross tenant item',
            'quantity' => '1.00',
            'rate' => '100.00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when recording a payment on another tenants invoice', function () {
    $invoice = ClientInvoice::factory()->for($this->workspaceB['freelancer'])->sent()->create([
        'subtotal' => 1000,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1000,
    ]);

    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->postJson($this->apiUrl("client-invoices/{$invoice->id}/payments"), [
            'amount' => '100.00',
            'payment_method' => 'manual',
            'paid_at' => '2026-03-15T10:00:00+00:00',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('returns not found when listing invoices for another tenants project', function () {
    $this->actingAsTenant($this->workspaceA['user'], $this->workspaceA['freelancer'])
        ->getJson($this->apiUrl("projects/{$this->projectB->id}/client-invoices"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

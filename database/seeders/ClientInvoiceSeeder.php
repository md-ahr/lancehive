<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class ClientInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        $acme = Client::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', DemoData::CLIENT_ACME_NAME)
            ->firstOrFail();
        $website = Project::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', 'Website Redesign')
            ->firstOrFail();

        $draftInvoice = ClientInvoice::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'invoice_number' => 'INV-2026-0001'],
            [
                'project_id' => $website->id,
                'status' => ClientInvoiceStatus::Draft,
                'currency' => 'BDT',
                'subtotal' => 20000,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => 20000,
                'issued_at' => null,
                'due_date' => null,
                'sent_at' => null,
                'paid_at' => null,
                'notes' => 'Payment via bank transfer within 30 days.',
                'bill_to_name' => $acme->name,
                'bill_to_email' => $acme->contact_email,
                'bill_to_address' => '123 Demo Street, Dhaka, Bangladesh',
            ],
        );

        ClientInvoiceItem::query()->updateOrCreate(
            ['client_invoice_id' => $draftInvoice->id, 'description' => 'Design — homepage mockups'],
            [
                'quantity' => 8,
                'rate' => 2500,
                'amount' => 20000,
            ],
        );

        $sentInvoice = ClientInvoice::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'invoice_number' => 'INV-2026-0002'],
            [
                'project_id' => $website->id,
                'status' => ClientInvoiceStatus::Sent,
                'currency' => 'BDT',
                'subtotal' => 12500,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => 12500,
                'issued_at' => now()->subDays(15)->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'sent_at' => now()->subDays(15),
                'paid_at' => null,
                'notes' => 'Thank you for your business.',
                'bill_to_name' => $acme->name,
                'bill_to_email' => $acme->contact_email,
                'bill_to_address' => '123 Demo Street, Dhaka, Bangladesh',
            ],
        );

        ClientInvoiceItem::query()->updateOrCreate(
            ['client_invoice_id' => $sentInvoice->id, 'description' => 'Development — layout sprint'],
            [
                'quantity' => 5,
                'rate' => 2500,
                'amount' => 12500,
            ],
        );

        ClientInvoicePayment::query()->updateOrCreate(
            [
                'client_invoice_id' => $sentInvoice->id,
                'reference' => 'BT-DEMO-001',
            ],
            [
                'amount' => 5000,
                'payment_method' => ClientPaymentMethod::BankTransfer,
                'paid_at' => now()->subDays(5),
                'notes' => 'Partial payment received.',
            ],
        );
    }
}

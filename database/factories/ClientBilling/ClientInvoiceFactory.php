<?php

declare(strict_types=1);

namespace Database\Factories\ClientBilling;

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientInvoice>
 */
class ClientInvoiceFactory extends Factory
{
    protected $model = ClientInvoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 50000);

        return [
            'project_id' => Project::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('2026-####'),
            'status' => ClientInvoiceStatus::Draft,
            'currency' => 'BDT',
            'subtotal' => $subtotal,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
            'issued_at' => null,
            'due_date' => null,
            'sent_at' => null,
            'paid_at' => null,
            'notes' => fake()->optional()->sentence(),
            'bill_to_name' => fake()->company(),
            'bill_to_email' => fake()->companyEmail(),
            'bill_to_address' => fake()->optional()->address(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => ClientInvoiceStatus::Sent,
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => ClientInvoiceStatus::Paid,
            'issued_at' => now()->subDays(15)->toDateString(),
            'due_date' => now()->subDays(1)->toDateString(),
            'sent_at' => now()->subDays(15),
            'paid_at' => now(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ClientInvoice $invoice): void {
            if ($invoice->freelancer_id !== null || $invoice->project_id === null) {
                return;
            }

            $project = Project::withoutGlobalScopes()->find($invoice->project_id);

            if ($project !== null) {
                $invoice->freelancer_id = $project->freelancer_id;
            }
        });
    }
}

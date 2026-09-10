<?php

declare(strict_types=1);

namespace Database\Factories\ClientBilling;

use App\Features\ClientBilling\Enums\ClientPaymentMethod;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientInvoicePayment>
 */
class ClientInvoicePaymentFactory extends Factory
{
    protected $model = ClientInvoicePayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_invoice_id' => ClientInvoice::factory(),
            'amount' => fake()->randomFloat(2, 500, 10000),
            'payment_method' => ClientPaymentMethod::Manual,
            'reference' => fake()->optional()->uuid(),
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

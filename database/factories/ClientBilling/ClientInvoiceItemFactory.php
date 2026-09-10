<?php

declare(strict_types=1);

namespace Database\Factories\ClientBilling;

use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientInvoiceItem>
 */
class ClientInvoiceItemFactory extends Factory
{
    protected $model = ClientInvoiceItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 40);
        $rate = fake()->randomFloat(2, 500, 5000);

        return [
            'client_invoice_id' => ClientInvoice::factory(),
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => round($quantity * $rate, 2),
        ];
    }
}

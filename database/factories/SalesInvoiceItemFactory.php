<?php

namespace Database\Factories;

use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesInvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sales_invoice_id' => SalesInvoice::factory(),
            'description' => fake()->text(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999.99),
            'unit_price' => fake()->randomFloat(2, 0, 9999999999.99),
            'total' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseInvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'purchase_invoice_id' => PurchaseInvoice::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999.99),
            'unit_price' => fake()->randomFloat(2, 0, 9999999999.99),
            'total' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}

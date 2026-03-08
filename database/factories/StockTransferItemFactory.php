<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'stock_transfer_id' => StockTransfer::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}

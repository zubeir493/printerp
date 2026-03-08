<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity_on_hand' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}

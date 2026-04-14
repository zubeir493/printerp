<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_transfer_reduces_source_and_increases_destination()
    {
        $fromWarehouse = Warehouse::create([
            'name' => 'Source Warehouse',
            'code' => 'SRC',
        ]);

        $toWarehouse = Warehouse::create([
            'name' => 'Destination Warehouse',
            'code' => 'DST',
        ]);

        $item = InventoryItem::create([
            'name' => 'Corrugated Board',
            'sku' => 'BOARD-CRC',
            'unit' => 'Sheet',
            'purchase_unit' => 'Bundle',
            'conversion_factor' => 250,
            'type' => 'raw_material',
            'is_sellable' => false,
            'price' => 12.00,
            'average_cost' => 12.00,
        ]);

        InventoryBalance::create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $fromWarehouse->id,
            'quantity_on_hand' => 500,
        ]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'ST-TEST-001',
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'transfer_date' => now(),
            'status' => 'draft',
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $item->id,
            'quantity' => 150,
        ]);

        $transfer->update(['status' => 'completed']);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'warehouse_id' => $fromWarehouse->id,
            'quantity_on_hand' => 350.00,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'warehouse_id' => $toWarehouse->id,
            'quantity_on_hand' => 150.00,
        ]);
    }

    public function test_stock_transfer_cannot_complete_when_insufficient_stock()
    {
        $this->expectException(\Exception::class);

        $fromWarehouse = Warehouse::create([
            'name' => 'Source Warehouse 2',
            'code' => 'SRC2',
        ]);

        $toWarehouse = Warehouse::create([
            'name' => 'Destination Warehouse 2',
            'code' => 'DST2',
        ]);

        $item = InventoryItem::create([
            'name' => 'Adhesive Tape',
                'sku' => 'TAPE-ADH',
                'unit' => 'Roll',
                'purchase_unit' => 'Roll',
                'conversion_factor' => 1,
                'type' => 'raw_material',
                'is_sellable' => false,
                'price' => 4.50,
                'average_cost' => 4.50,
        ]);

        InventoryBalance::create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $fromWarehouse->id,
            'quantity_on_hand' => 50,
        ]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'ST-TEST-002',
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'transfer_date' => now(),
            'status' => 'draft',
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
        ]);

        $transfer->update(['status' => 'completed']);
    }
}

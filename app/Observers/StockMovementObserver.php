<?php

namespace App\Observers;

use App\Models\InventoryBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;

class StockMovementObserver
{
    /**
     * Handle the StockMovement "created" event.
     */
    public function created(StockMovement $stockMovement): void
    {
        Log::info('StockMovementObserver@created', ['id' => $stockMovement->id]);

        $quantityChange = (float) $stockMovement->quantity;
        $this->validateBalanceChange($stockMovement->inventory_item_id, $stockMovement->warehouse_id, $quantityChange);

        $balance = InventoryBalance::firstOrCreate(
            [
                'inventory_item_id' => $stockMovement->inventory_item_id,
                'warehouse_id' => $stockMovement->warehouse_id,
            ],
            ['quantity_on_hand' => 0]
        );

        $oldQty = (float) $balance->quantity_on_hand;
        $balance->quantity_on_hand = $oldQty + $quantityChange;
        $balance->save();

        Log::info('Balance updated', [
            'item' => $stockMovement->inventory_item_id,
            'warehouse' => $stockMovement->warehouse_id,
            'old_qty' => $oldQty,
            'new_qty' => $balance->quantity_on_hand
        ]);
    }

    public function updating(StockMovement $stockMovement): void
    {
        $original = $stockMovement->getOriginal();

        $oldItemId = $original['inventory_item_id'];
        $oldWarehouseId = $original['warehouse_id'];
        $oldQuantity = (float) $original['quantity'];

        $newItemId = $stockMovement->inventory_item_id;
        $newWarehouseId = $stockMovement->warehouse_id;
        $newQuantity = (float) $stockMovement->quantity;

        if ($oldItemId === $newItemId && $oldWarehouseId === $newWarehouseId) {
            $delta = $newQuantity - $oldQuantity;
            $this->validateBalanceChange($newItemId, $newWarehouseId, $delta);
            return;
        }

        $this->validateBalanceChange($oldItemId, $oldWarehouseId, -$oldQuantity);
        $this->validateBalanceChange($newItemId, $newWarehouseId, $newQuantity);
    }

    public function deleted(StockMovement $stockMovement): void
    {
        $quantityChange = -(float) $stockMovement->quantity;
        $this->validateBalanceChange($stockMovement->inventory_item_id, $stockMovement->warehouse_id, $quantityChange);
    }

    private function validateBalanceChange(int $inventoryItemId, int $warehouseId, float $delta): void
    {
        $balance = InventoryBalance::firstWhere([
            'inventory_item_id' => $inventoryItemId,
            'warehouse_id' => $warehouseId,
        ]);

        $currentQty = $balance ? (float) $balance->quantity_on_hand : 0.0;
        $resultingQty = $currentQty + $delta;

        if ($resultingQty < 0) {
            throw new \Exception(sprintf(
                'Cannot apply this stock movement because item %s in warehouse %s would go negative (%s).',
                $inventoryItemId,
                $warehouseId,
                number_format($resultingQty, 2)
            ));
        }
    }
}

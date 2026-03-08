<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\InventoryBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function receiveStock(
        int $itemId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        string $referenceType,
        int $referenceId
    ) {
        $item = InventoryItem::findOrFail($itemId);
        $warehouse = Warehouse::findOrFail($warehouseId);

        $this->createMovement(
            $item,
            $warehouse,
            $referenceType === 'material_return' ? 'material_return' : 'purchase',
            $quantity,
            $unitCost,
            $referenceType,
            $referenceId
        );
    }

    public function createMovement(
        InventoryItem $item,
        Warehouse $warehouse,
        string $type,
        float $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ) {
        DB::transaction(function () use (
            $item,
            $warehouse,
            $type,
            $quantity,
            $unitCost,
            $referenceType,
            $referenceId
        ) {

            $signedQty = $this->resolveSignedQuantity($type, $quantity);

            // 1️⃣ Create Stock Movement
            StockMovement::create([
                'inventory_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => $signedQty,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost ? $quantity * $unitCost : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'movement_date' => now(),
            ]);

            // 2️⃣ Update Inventory Balance
            $balance = InventoryBalance::firstOrCreate([
                'inventory_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ]);

            $balance->quantity_on_hand += $signedQty;

            if ($balance->quantity_on_hand < 0) {
                throw new \Exception("Negative stock not allowed.");
            }

            $balance->save();

            // 3️⃣ Update Average Cost (only purchase)
            if ($type === 'purchase') {
                $this->updateAverageCost($item, $quantity, $unitCost);
            }
        });
    }

    public function consumeStock(
        int $itemId,
        int $warehouseId,
        float $quantity,
        string $referenceType,
        int $referenceId
    ) {
        DB::transaction(function () use ($itemId, $warehouseId, $quantity, $referenceType, $referenceId) {
            $balance = InventoryBalance::where([
                'inventory_item_id' => $itemId,
                'warehouse_id' => $warehouseId,
            ])->lockForUpdate()->first();

            if (!$balance || $balance->quantity_on_hand < $quantity) {
                $itemName = InventoryItem::find($itemId)?->name ?? "Item #{$itemId}";
                throw new \Exception("Insufficient stock for {$itemName} in the selected warehouse.");
            }

            $item = InventoryItem::findOrFail($itemId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            // Create Stock Movement
            StockMovement::create([
                'inventory_item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'type' => $referenceType === 'material_issue' ? 'material_issue' : 'consumption',
                'quantity' => -$quantity,
                'unit_cost' => $item->average_cost,
                'total_cost' => $quantity * $item->average_cost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'movement_date' => now(),
            ]);

            // Update Balance
            $balance->quantity_on_hand -= $quantity;
            $balance->save();
        });
    }

    private function resolveSignedQuantity(string $type, float $quantity): float
    {
        return match ($type) {
            'purchase', 'transfer_in', 'adjustment', 'material_return' => $quantity,
            'consumption', 'transfer_out', 'material_issue' => -$quantity,
            default => $quantity,
        };
    }

    private function updateAverageCost(InventoryItem $item, float $qty, float $cost)
    {
        $currentQty = $item->inventoryBalances()->sum('quantity_on_hand');
        $currentValue = $currentQty * $item->average_cost;

        $newValue = $currentValue + ($qty * $cost);
        $newQty = $currentQty + $qty;

        if ($newQty > 0) {
            $item->average_cost = $newValue / $newQty;
            $item->save();
        }
    }
}

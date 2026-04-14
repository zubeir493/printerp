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
        $signedQty = $this->resolveSignedQuantity($type, $quantity);

        return StockMovement::create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'type' => $type,
            'quantity' => $signedQty,
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost ? abs($quantity) * $unitCost : null,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'movement_date' => now(),
        ]);
    }

    public function consumeStock(
        int $itemId,
        int $warehouseId,
        float $quantity,
        string $referenceType,
        int $referenceId
    ) {
        $item = InventoryItem::findOrFail($itemId);
        $warehouse = Warehouse::findOrFail($warehouseId);

        // Pre-validation (Optional but good for UX)
        $balance = InventoryBalance::where([
            'inventory_item_id' => $itemId,
            'warehouse_id' => $warehouseId,
        ])->first();

        if (!$balance || $balance->quantity_on_hand < $quantity) {
             throw new \Exception("Insufficient stock for {$item->name} in the selected warehouse.");
        }

        return $this->createMovement(
            $item,
            $warehouse,
            'consumption',
            $quantity,
            $item->price,
            $referenceType,
            $referenceId
        );
    }

    private function resolveSignedQuantity(string $type, float $quantity): float
    {
        return match ($type) {
            'purchase', 'transfer_in', 'material_return', 'production_output' => abs($quantity),
            'consumption', 'transfer_out', 'sale' => -abs($quantity),
            'adjustment' => $quantity, // Preserve sign for manual adjustments
            default => $quantity,
        };
    }
}

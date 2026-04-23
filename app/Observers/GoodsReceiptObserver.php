<?php

namespace App\Observers;

use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoodsReceiptObserver
{
    public function updated(GoodsReceipt $receipt): void
    {
        // Check if status changed to posted and it hasn't been processed yet
        if ($receipt->wasChanged('status') && $receipt->status === 'posted' && is_null($receipt->posted_at)) {
            DB::transaction(function () use ($receipt) {
                if (!$receipt->warehouse_id) {
                    throw new \Exception("Warehouse ID not selected for Goods Receipt ID: {$receipt->id}");
                }

                $inventoryService = app(\App\Services\InventoryService::class);

                foreach ($receipt->items as $item) {
                    $poItem = $item->purchaseOrderItem;
                    if (!$poItem) continue;

                    // 1. Move stock (Handles unit conversion internally)
                    $inventoryService->receiveStockInPurchaseUnit(
                        $poItem->inventory_item_id,
                        $receipt->warehouse_id,
                        $item->quantity_received,
                        $poItem->unit_price,
                        get_class($receipt),
                        $receipt->id
                    );

                    // 2. Update received quantity on PO Item
                    $poItem->increment('received_quantity', $item->quantity_received);
                }

                $receipt->updateQuietly(['posted_at' => now()]);
            });
        }
    }
}

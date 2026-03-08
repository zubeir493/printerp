<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'posted_at',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
    protected static function booted()
    {
        static::saving(function ($receipt) {
            \Illuminate\Support\Facades\Log::info("Goods Receipt Saving Event", [
                'id' => $receipt->id,
                'status' => $receipt->status,
                'dirty' => $receipt->getDirty(),
                'original' => $receipt->getOriginal(),
            ]);
        });

        static::updated(function ($receipt) {
            \Illuminate\Support\Facades\Log::info("Goods Receipt Updated Event", [
                'id' => $receipt->id,
                'status' => $receipt->status,
                'wasChanged_status' => $receipt->wasChanged('status'),
                'wasChanged' => $receipt->getChanges(),
            ]);

            if ($receipt->wasChanged('status') && $receipt->status === 'posted' && is_null($receipt->posted_at)) {
                try {
                    \Illuminate\Support\Facades\Log::info("Attempting to post Goods Receipt ID: {$receipt->id}");

                    if (!$receipt->warehouse_id) {
                        throw new \Exception("Warehouse ID not selected for Goods Receipt ID: {$receipt->id}");
                    }

                    $warehouse = \App\Models\Warehouse::find($receipt->warehouse_id);
                    if (!$warehouse) {
                        throw new \Exception("Warehouse not found for ID: {$receipt->warehouse_id}");
                    }

                    $inventoryService = app(\App\Services\InventoryService::class);

                    foreach ($receipt->items as $item) {
                        $poItem = $item->purchaseOrderItem;
                        if (!$poItem) {
                            throw new \Exception("Purchase Order Item not found for Goods Receipt Item ID: {$item->id}");
                        }

                        $inventoryItem = $poItem->inventoryItem;
                        if (!$inventoryItem) {
                            throw new \Exception("Inventory Item not found for Purchase Order Item ID: {$poItem->id}");
                        }

                        $baseQuantity = $item->quantity_received;
                        $factor = (float)($inventoryItem->conversion_factor ?: 1);

                        if ($inventoryItem->conversion_factor) {
                            $baseQuantity = $item->quantity_received * $factor;
                        }

                        // 1️⃣ Move stock
                        $inventoryService->createMovement(
                            $inventoryItem,
                            $receipt->warehouse,
                            'purchase',
                            $baseQuantity,
                            $poItem->unit_price / $factor,
                            self::class,
                            $receipt->id
                        );

                        // 2️⃣ Update received quantity
                        $poItem->increment('received_quantity', $item->quantity_received);
                    }

                    $receipt->updateQuietly(['posted_at' => now()]);
                    \Illuminate\Support\Facades\Log::info("Successfully posted Goods Receipt ID: {$receipt->id}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to post Goods Receipt ID: {$receipt->id}. Error: " . $e->getMessage());
                    // Re-throw to ensure the transaction rolls back and the user sees the error
                    throw $e;
                }
            }
        });
    }
}

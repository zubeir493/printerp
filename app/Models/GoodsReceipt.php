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
            if ($receipt->wasChanged('status') && $receipt->status === 'posted' && is_null($receipt->posted_at)) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($receipt) {
                    if (!$receipt->warehouse_id) {
                        throw new \Exception("Warehouse ID not selected for Goods Receipt ID: {$receipt->id}");
                    }

                    $inventoryService = app(\App\Services\InventoryService::class);

                    foreach ($receipt->items as $item) {
                        $poItem = $item->purchaseOrderItem;
                        if (!$poItem) continue;

                        $inventoryItem = $poItem->inventoryItem;
                        $factor = (float)($inventoryItem?->conversion_factor ?: 1);
                        $baseQuantity = $item->quantity_received * $factor;

                        // 1. Move stock
                        $inventoryService->createMovement(
                            $inventoryItem,
                            $receipt->warehouse,
                            'purchase',
                            $baseQuantity,
                            $poItem->unit_price / $factor,
                            self::class,
                            $receipt->id
                        );

                        // 2. Update received quantity on PO Item
                        $poItem->increment('received_quantity', $item->quantity_received);
                        
                        // 3. Trigger PO item sync (status and subtotal)
                        // This will trigger PurchaseOrderItemObserver@saved
                        $poItem->save(); 
                    }

                    $receipt->updateQuietly(['posted_at' => now()]);
                });
            }
        });
    }
}

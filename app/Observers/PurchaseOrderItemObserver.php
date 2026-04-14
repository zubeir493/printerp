<?php

namespace App\Observers;

use App\Models\PurchaseOrderItem;

class PurchaseOrderItemObserver
{
    public function saved(PurchaseOrderItem $item): void
    {
        $this->syncPurchaseOrderStatus($item->purchaseOrder);
    }

    public function deleted(PurchaseOrderItem $item): void
    {
        $this->syncPurchaseOrderStatus($item->purchaseOrder);
    }

    private function syncPurchaseOrderStatus(?\App\Models\PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder) {
            // Re-calculate financial subtotal whenever an item changes
            $purchaseOrder->recalculateSubtotal();

            $allItems = $purchaseOrder->purchaseOrderItems()->get();
            
            if ($allItems->count() > 0) {
                $allFinished = $allItems->every(fn($i) => in_array($i->status, ['received', 'cancelled']));
                $hasReceived = $allItems->contains('status', 'received');

                if ($allFinished) {
                    if ($hasReceived) {
                        if ($purchaseOrder->status !== 'received') {
                            $purchaseOrder->update(['status' => 'received']);
                        }
                    } else { // All items are cancelled!
                        if ($purchaseOrder->status !== 'cancelled') {
                           $purchaseOrder->update(['status' => 'cancelled']);
                        }
                    }
                } elseif ($allItems->contains('status', 'partially_received')) {
                    // Logic: If any item is partially received, the order is 'partially_received'
                    if (in_array($purchaseOrder->status, ['draft', 'approved'])) {
                        $purchaseOrder->update(['status' => 'partially_received']);
                    }
                }
            }
        }
    }
}

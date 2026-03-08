<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Services\Accounting\CreatePurchaseOrderJournalEntry;

class PurchaseOrderObserver
{
    public function saved(PurchaseOrder $purchaseOrder)
    {
        \Illuminate\Support\Facades\Log::info('PurchaseOrderObserver@saved', [
            'id' => $purchaseOrder->id,
            'status' => $purchaseOrder->status,
            'was_status_changed' => $purchaseOrder->wasChanged('status'),
            'original_status' => $purchaseOrder->getOriginal('status'),
        ]);

        if ($purchaseOrder->wasChanged('status') && $purchaseOrder->status === 'received') {
            \Illuminate\Support\Facades\Log::info('Triggering CreatePurchaseOrderJournalEntry');
            app(CreatePurchaseOrderJournalEntry::class)->handle($purchaseOrder);
        }
    }
}

<?php

namespace App\Observers;

use App\Models\SalesOrder;
use App\Services\Accounting\CreateSalesJournalEntry;

class SalesOrderObserver
{
    public function saved(SalesOrder $salesOrder)
    {
        \Illuminate\Support\Facades\Log::info('SalesOrderObserver@saved', [
            'id' => $salesOrder->id,
            'status' => $salesOrder->status,
            'was_status_changed' => $salesOrder->wasChanged('status'),
        ]);

        if ($salesOrder->wasChanged('status') && $salesOrder->status === 'completed') {
            \Illuminate\Support\Facades\Log::info('Triggering CreateSalesJournalEntry');
            app(CreateSalesJournalEntry::class)->handle($salesOrder);
        }
    }
}

<?php

namespace App\Observers;

use App\Models\SalesOrder;
use App\Services\Accounting\CreateSalesJournalEntry;
use App\Services\SalesOrderPaymentService;

class SalesOrderObserver
{
    public function saved(SalesOrder $salesOrder)
    {
        if ($salesOrder->wasChanged('status') && $salesOrder->status === 'completed') {
            app(CreateSalesJournalEntry::class)->handle($salesOrder);
            app(SalesOrderPaymentService::class)->createImmediatePaymentForCashSale($salesOrder);
        }
    }
}

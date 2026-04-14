<?php

namespace App\Observers;

use App\Models\SalesOrderItem;

class SalesOrderItemObserver
{
    public function saved(SalesOrderItem $item): void
    {
        $item->salesOrder?->recalculateTotal();
    }

    public function deleted(SalesOrderItem $item): void
    {
        $item->salesOrder?->recalculateTotal();
    }
}

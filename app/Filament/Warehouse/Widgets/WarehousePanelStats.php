<?php

namespace App\Filament\Warehouse\Widgets;

use App\Models\Dispatch;
use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarehousePanelStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $dailyDispatches = Dispatch::query()
            ->whereDate('delivery_date', today())
            ->count();

        $pendingReceipts = PurchaseOrder::query()
            ->where('status', 'approved')
            ->whereDoesntHave('goodsReceipts')
            ->count();

        return [
            Stat::make('Daily Dispatches', $dailyDispatches)
                ->description('Scheduled for today')
                ->color('primary'),
            Stat::make('Pending Receipts', $pendingReceipts)
                ->description('Approved POs without goods receipts')
                ->color($pendingReceipts > 0 ? 'warning' : 'success'),
        ];
    }
}

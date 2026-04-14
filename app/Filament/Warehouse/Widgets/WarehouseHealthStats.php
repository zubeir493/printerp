<?php

namespace App\Filament\Warehouse\Widgets;

use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;

class WarehouseHealthStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Dead Stock Risk
        // Items with positive balance but NO movements in 90 days
        $deadStockCount = \App\Models\InventoryItem::whereHas('inventoryBalances', function ($q) {
                $q->where('quantity_on_hand', '>', 0);
            })
            ->whereDoesntHave('stockMovements', function ($q) {
                $q->where('movement_date', '>=', now()->subDays(90));
            })
            ->count();

        // 2. Dispatch Throughput Today
        $dispatchesToday = \App\Models\Dispatch::whereDate('created_at', Carbon::today())->count();

        // 3. Low Stock Items
        $lowStockCount = \App\Models\InventoryItem::whereHas('inventoryBalances', function ($q) {
                // Simplified check: item is low if total QOH across warehouses < 10
                // Ideally this would check against a 'reorder_point' field if it existed.
                $q->selectRaw('SUM(quantity_on_hand) as total_qoh')
                  ->having('total_qoh', '<', 10);
            })->count();

        return [
            Stat::make('Dead Stock Risk', $deadStockCount . ' Items')
                ->description('No movement in 90+ days')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($deadStockCount > 10 ? 'danger' : 'success'),
            
            Stat::make('Outbound Today', $dispatchesToday)
                ->description('Dispatches leaving today')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
                
            Stat::make('Low Stock Alerts', $lowStockCount)
                ->description('Items nearing depletion')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),
        ];
    }
}

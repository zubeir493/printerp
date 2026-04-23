<?php

namespace App\Filament\Widgets;

use App\Models\InventoryItem;
use App\Models\InventoryBalance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalItems = InventoryItem::count();
        $totalValue = InventoryBalance::join('inventory_items', 'inventory_items.id', '=', 'inventory_balances.inventory_item_id')
            ->sum(\DB::raw('inventory_balances.quantity_on_hand * (CASE WHEN inventory_items.type = "raw_material" AND inventory_items.conversion_factor > 0 THEN CAST(inventory_items.price AS DECIMAL) / CAST(inventory_items.conversion_factor AS DECIMAL) WHEN inventory_items.type IN ("tools", "spare_parts", "wip") THEN 0 ELSE CAST(inventory_items.price AS DECIMAL) END)'));
        
        $lowStockCount = InventoryBalance::where('quantity_on_hand', '<', 10)->count(); // Simplified low stock logic

        return [
            Stat::make('Total SKU count', $totalItems)
                ->description('Active items in catalog')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),
            Stat::make('Total Inventory Value', number_format($totalValue, 2) . ' ETB')
                ->description('Current valuation')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Low Stock Items', $lowStockCount)
                ->description('Items below threshold')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}

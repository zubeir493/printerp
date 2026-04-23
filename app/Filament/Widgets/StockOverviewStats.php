<?php

namespace App\Filament\Widgets;

use App\Models\InventoryBalance;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockOverviewStats extends BaseWidget
{
    public ?int $warehouse_id = null;

    protected function getStats(): array
    {
        $query = InventoryBalance::query();
        
        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        $totalItems = (clone $query)->where('quantity_on_hand', '>', 0)->count();
        $totalValue = (clone $query)->join('inventory_items', 'inventory_items.id', '=', 'inventory_balances.inventory_item_id')
            ->sum(\DB::raw('inventory_balances.quantity_on_hand * (CASE WHEN inventory_items.type = "raw_material" AND inventory_items.conversion_factor > 0 THEN CAST(inventory_items.price AS DECIMAL) / CAST(inventory_items.conversion_factor AS DECIMAL) WHEN inventory_items.type IN ("tools", "spare_parts", "wip") THEN 0 ELSE CAST(inventory_items.price AS DECIMAL) END)'));
        
        $warehouseName = $this->warehouse_id ? Warehouse::find($this->warehouse_id)?->name : 'All Warehouses';

        return [
            Stat::make('Total SKU count (' . $warehouseName . ')', $totalItems)
                ->description('Active items with stock')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),
            Stat::make('Warehouse Valuation', number_format($totalValue, 2) . ' ETB')
                ->description('Total value of stock on hand')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}

<?php

namespace App\Filament\Exports;

use App\Models\InventoryBalance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InventoryBalanceExporter extends Exporter
{
    protected static ?string $model = InventoryBalance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('inventoryItem.name')
                ->label('Item'),
            ExportColumn::make('warehouse.name')
                ->label('Warehouse'),
            ExportColumn::make('quantity_on_hand')
                ->label('Quantity')
                ->formatStateUsing(function ($state, $record) {
                    $item = $record->inventoryItem;
                    if (!$item) return number_format($state);

                    $type = $item->type instanceof \BackedEnum ? $item->type->value : $item->type;
                    if (strtolower((string)$type) === 'raw_material' && $item->purchase_unit && $item->conversion_factor > 0) {
                        return number_format((float)$state / (float)$item->conversion_factor, 2) . ' ' . $item->purchase_unit;
                    }
                    return number_format($state) . ' ' . $item->unit;
                }),
            ExportColumn::make('total_value')
                ->label('Total Value (Birr)')
                ->state(function (InventoryBalance $record): float {
                    return (float) $record->quantity_on_hand * (float) ($record->inventoryItem->price ?? 0);
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inventory balance export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

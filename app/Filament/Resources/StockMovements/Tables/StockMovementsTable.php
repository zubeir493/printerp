<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inventoryItem.name')
                    ->label('Item / Warehouse')
                    ->description(fn ($record) => $record->warehouse?->name)
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'purchase', 'transfer_in', 'material_return', 'production_output' => 'success',
                        'transfer_out', 'consumption' => 'danger',
                        'dispatch' => 'warning',
                        'adjustment' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('movement_date')
                    ->label('Time | Date')
                    ->dateTime('h:i A | d M')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'purchase' => 'Purchase',
                        'consumption' => 'Consumption',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'dispatch' => 'Dispatch',
                        'adjustment' => 'Adjustment',
                        'material_return' => 'Material Return',
                        'production_output' => 'Production Output',
                    ]),
            ])
            ->recordActions([
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\StockMovementExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\StockMovementExporter::class),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'purchase', 'transfer_in', 'material_return' => 'success',
                        'production_output' => 'info',
                        'transfer_out', 'consumption' => 'danger',
                        'adjustment' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('movement_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'purchase' => 'Purchase',
                        'consumption' => 'Consumption',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'adjustment' => 'Adjustment',
                        'material_return' => 'Material Return',
                        'production_output' => 'Production Output',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\StockMovementExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\StockMovementExporter::class)
                ]),
            ]);
    }
}

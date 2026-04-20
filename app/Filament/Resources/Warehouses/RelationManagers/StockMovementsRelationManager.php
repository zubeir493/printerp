<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Stock Movements';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('movement_date')
                    ->label('Time | Date')
                    ->dateTime('h:i A | d M')
                    ->sortable(),

                TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'purchase', 'transfer_in', 'material_return', 'production_output' => 'success',
                        'transfer_out', 'consumption' => 'danger',
                        'dispatch' => 'warning',
                        'adjustment' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),
            ])
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
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('movement_date', 'desc');
    }
}

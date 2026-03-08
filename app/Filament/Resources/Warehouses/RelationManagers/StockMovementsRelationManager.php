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
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'purchase' => 'success',
                        'consumption' => 'danger',
                        'transfer_in' => 'info',
                        'transfer_out' => 'warning',
                        'adjustment' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('ETB')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('ETB')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('reference_type')
                    ->label('Ref Type')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reference_id')
                    ->label('Ref ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'purchase' => 'Purchase',
                        'consumption' => 'Consumption',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'adjustment' => 'Adjustment',
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

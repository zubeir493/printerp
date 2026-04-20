<?php

namespace App\Filament\Resources\InventoryItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Item')
                    ->description(fn ($record) => $record->sku)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'raw_material' => 'gray',
                        'finished_good' => 'success',
                        'wip' => 'info',
                        'tools' => 'warning',
                        'spare_parts' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('unit'),
                TextColumn::make('price')
                    ->label('Value / Price')
                    ->money('ETB')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_good' => 'Finished Good',
                        'wip' => 'Produced (Undispatched)',
                        'tools' => 'Tools',
                        'spare_parts' => 'Spare Parts',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\InventoryItemExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\InventoryItemExporter::class)
                ]),
            ]);
    }
}

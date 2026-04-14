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
                TextColumn::make('name')->searchable(),
                TextColumn::make('sku'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'raw_material' => 'gray',
                        'finished_good' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('unit'),
                TextColumn::make('price')
                    ->label('Price / Value')
                    ->suffix(' Birr')
            ])
            ->filters([
                //
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

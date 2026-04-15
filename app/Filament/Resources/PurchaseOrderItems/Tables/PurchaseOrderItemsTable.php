<?php

namespace App\Filament\Resources\PurchaseOrderItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrderItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.partner.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Order Qty')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partially_received' => 'Partially Received',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ])
                    ->sortable()
                    ->searchable(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PurchaseOrderItemExporter::class)
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partially_received' => 'Partially Received',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PurchaseOrderItemExporter::class)
                ]),
            ]);
    }
}

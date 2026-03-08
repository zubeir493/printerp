<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('receipt_number')
                    ->required()
                    ->disabled(fn($record) => $record?->status === 'posted'),
                Select::make('purchase_order_id')
                    ->relationship('purchaseOrder', 'po_number')
                    ->required()
                    ->reactive()
                    ->disabled(fn($record) => $record?->status === 'posted'),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->disabled(fn($record) => $record?->status === 'posted'),
                DatePicker::make('receipt_date')
                    ->required()
                    ->disabled(fn($record) => $record?->status === 'posted'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                    ])
                    ->required()
                    ->default('draft')
                    ->disabled(fn($record) => $record?->status === 'posted')
                    ->dehydrated(true),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('purchase_order_item_id')
                            ->options(function ($get) {
                                $purchaseOrderId = $get('../../purchase_order_id');

                                if (! $purchaseOrderId) {
                                    return [];
                                }

                                return \App\Models\PurchaseOrderItem::query()
                                    ->where('purchase_order_id', $purchaseOrderId)
                                    ->with('inventoryItem')
                                    ->get()
                                    ->pluck('inventoryItem.name', 'id');
                            })
                            ->required()
                            ->label('Item')
                            ->disabled(fn($record) => $record?->status === 'posted'),

                        TextInput::make('quantity_received')
                            ->numeric()
                            ->required()
                            ->disabled(fn($record) => $record?->status === 'posted'),
                    ])
                    ->minItems(1)
                    ->disabled(fn($record) => $record?->status === 'posted')
                    ->addable(false)

            ]);
    }
}

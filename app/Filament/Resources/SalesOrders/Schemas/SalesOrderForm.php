<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;
use Filament\Schemas\Components\Utilities\Set as UtilitiesSet;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('warehouse_id')
                                    ->relationship('warehouse', 'name')
                                    ->required(),
                                Select::make('partner_id')
                                    ->label('Customer (Optional)')
                                    ->relationship('partner', 'name')
                                    ->searchable(),
                                DatePicker::make('order_date')
                                    ->required()
                                    ->default(now()),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'completed' => 'Completed',
                                        'void' => 'Void',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                    ]),
                Section::make('Order Items')
                    ->schema([
                        Repeater::make('salesOrderItems')
                            ->relationship('salesOrderItems')
                            ->schema([
                                Select::make('inventory_item_id')
                                    ->label('Finished Good')
                                    ->options(\App\Models\InventoryItem::where('type', 'finished_good')->where('is_sellable', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $unitPrice = \App\Models\InventoryItem::find($state)?->average_cost ?? 0;
                                        $set('unit_price', $unitPrice);
                                        $set('total', ($get('quantity') ?? 1) * $unitPrice);

                                        $subtotal = collect($get('../../salesOrderItems'))->sum(function ($item) {
                                            return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                                        });
                                        $set('../../subtotal', $subtotal);
                                        $set('../../total', $subtotal);
                                    }),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $set('total', $state * ($get('unit_price') ?? 0));

                                        $subtotal = collect($get('../../salesOrderItems'))->sum(function ($item) {
                                            return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                                        });
                                        $set('../../subtotal', $subtotal);
                                        $set('../../total', $subtotal);
                                    }),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $set('total', $state * ($get('quantity') ?? 1));

                                        $subtotal = collect($get('../../salesOrderItems'))->sum(function ($item) {
                                            return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                                        });
                                        $set('../../subtotal', $subtotal);
                                        $set('../../total', $subtotal);
                                    }),
                                TextInput::make('total')
                                    ->numeric()
                                    ->required()
                                    ->readOnly(),
                            ])
                            ->columns(4)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (UtilitiesGet $get, UtilitiesSet $set) {
                                $subtotal = collect($get('salesOrderItems'))->sum(function ($item) {
                                    return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                                });
                                $set('subtotal', $subtotal);
                                $set('total', $subtotal);
                            }),
                    ]),
                Section::make('Totals')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                                TextInput::make('total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}

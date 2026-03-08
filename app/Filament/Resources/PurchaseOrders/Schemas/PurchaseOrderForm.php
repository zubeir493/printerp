<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()
                    ->schema([

                        Grid::make(3)
                            ->schema([
                                TextInput::make('po_number')
                                    ->label('Purchase Order no.')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->dehydrated()
                                    ->disabled(fn($get) => $get('status') !== 'draft'),

                                Select::make('partner_id')
                                    ->label('Supplier')
                                    ->relationship('partner', 'name', modifyQueryUsing: fn($query) => $query->where('is_supplier', true))
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('phone')
                                            ->required(),
                                        TextInput::make('tin_number'),
                                        Hidden::make('is_supplier')->default(true),
                                    ])
                                    ->required()
                                    ->dehydrated()
                                    ->disabled(fn($get) => $get('status') !== 'draft'),

                                DatePicker::make('order_date')
                                    ->default(now())
                                    ->required()
                                    ->dehydrated()
                                    ->disabled(fn($get) => $get('status') !== 'draft'),
                            ]),

                        Repeater::make('purchaseOrderItems')
                            ->relationship('purchaseOrderItems')
                            ->schema([
                                Select::make('inventory_item_id')
                                    ->relationship('inventoryItem', 'name', fn($query) =>
                                    $query->select('id', 'name', 'purchase_unit'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $unit = \App\Models\InventoryItem::find($state)?->purchase_unit ?? '';
                                        $set('unit_label', $unit);
                                    })
                                    ->dehydrated()
                                    ->disabled(fn($get) => $get('../../status') !== 'draft'),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->suffix(fn($get) => $get('unit_label') ?? '')
                                    ->afterStateUpdated(function ($set, $get, $state) {
                                        // Update row total immediately
                                        $set('total', (float)($state ?? 0) * (float)($get('unit_price') ?? 0));

                                        // Update subtotal
                                        $subtotal = collect($get('../../purchaseOrderItems'))->sum(function ($item) {
                                            return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                                        });
                                        $set('../../subtotal', $subtotal);
                                    })
                                    ->dehydrated()
                                    ->disabled(fn($get) => $get('../../status') !== 'draft'),

                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->suffix('Birr')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($set, $get, $state) {
                                        // Update row total immediately
                                        $set('total', (float)($state ?? 0) * (float)($get('quantity') ?? 0));

                                        // Update subtotal
                                        $subtotal = collect($get('../../purchaseOrderItems'))->sum(function ($item) {
                                            return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                                        });
                                        $set('../../subtotal', $subtotal);
                                    })
                                    ->dehydrated()
                                    ->disabled(fn($get) => $get('../../status') !== 'draft'),

                                TextInput::make('total')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->suffix('Birr'),
                            ])
                            ->columns(4)
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                $subtotal = collect($get('purchaseOrderItems'))
                                    ->sum(fn($item) => $item['total'] ?? 0);

                                $set('subtotal', $subtotal);
                            })
                            ->defaultItems(1)
                            ->cloneable()
                            ->minItems(1),


                    ])->columnSpan(3),
                Section::make()
                    ->compact()
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'approved' => 'Approved',
                                'received' => 'Received',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                        TextInput::make('subtotal')
                            ->numeric()
                            ->suffix('Birr')
                            ->readOnly()
                            ->default(0),

                    ]),
            ])->columns(4);
    }
}

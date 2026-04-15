<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form; // Using Form instead of Schema if that's what Filament expects, but let's stick to what works. Actually the resource uses Schema.
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->relationship()
                    ->table([
                        TableColumn::make('Inventory Item'),
                        TableColumn::make('Available'),
                        TableColumn::make('Adjustment'),
                        TableColumn::make('Result'),
                    ])
                    ->compact()
                    ->schema([
                        Select::make('inventory_item_id')
                            ->relationship('inventoryItem', 'name')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $warehouseId = $get('../../warehouse_id');
                                if ($state && $warehouseId) {
                                    $balance = \App\Models\InventoryBalance::where('inventory_item_id', $state)
                                        ->where('warehouse_id', $warehouseId)
                                        ->first();
                                    $system = $balance ? (float)$balance->quantity_on_hand : 0;
                                    $set('system_quantity', $system);
                                    
                                    $adj = (float)$get('adjustment_quantity');
                                    $set('new_quantity', $system + $adj);
                                    $set('difference', $adj);
                                }
                            })
                            ->disabled(fn($record) => $record?->status === 'posted'),
                        TextInput::make('system_quantity')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('adjustment_quantity')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $system = (float)$get('system_quantity');
                                $adj = (float)$state;
                                $set('new_quantity', $system + $adj);
                                $set('difference', $adj);
                            })
                            ->helperText(fn($get) => (float)$get('system_quantity') + (float)$get('adjustment_quantity') < 0
                                ? 'This adjustment will create negative stock. Please lower the negative quantity or correct the system quantity.'
                                : null)
                            ->disabled(fn($record) => $record?->status === 'posted'),
                        TextInput::make('new_quantity')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->default(0)
                            ->helperText(fn($get) => (float)$get('new_quantity') < 0
                                ? 'Resulting stock would be negative. This adjustment cannot be posted.'
                                : null),
                        Hidden::make('difference')
                            ->default(0)
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->disabled(fn($record) => $record?->status === 'posted')
                    ->columnSpan(4),
                ComponentsSection::make()
                    ->schema([
                        TextInput::make('adjustment_number')
                            ->label('Adjustment Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->hidden(fn($operation) => $operation === 'create'),
                        Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn($set) => $set('items', []))
                            ->disabled(fn($record) => $record?->status === 'posted'),
                        DatePicker::make('adjustment_date')
                            ->default(now())
                            ->required()
                            ->disabled(fn($record) => $record?->status === 'posted'),
                        TextInput::make('reason')
                            ->disabled(fn($record) => $record?->status === 'posted'),
                        TextInput::make('status')
                            ->default('draft')
                            ->disabled()
                            ->dehydrated(),
                    ])->columnSpan(2),
            ])->columns(6);
    }
}

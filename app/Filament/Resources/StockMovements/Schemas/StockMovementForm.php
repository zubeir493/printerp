<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inventory_item_id')
                    ->relationship('inventoryItem', 'name')
                    ->required(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('reference_type'),
                TextInput::make('reference_id')
                    ->numeric(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_cost')
                    ->numeric()
                    ->suffix(' Birr'),
                TextInput::make('total_cost')
                    ->numeric()
                    ->suffix(' Birr'),
                DatePicker::make('movement_date')
                    ->required(),
            ]);
    }
}

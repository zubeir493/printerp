<?php

namespace App\Filament\Resources\InventoryBalances\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryBalanceForm
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
                TextInput::make('quantity_on_hand')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}

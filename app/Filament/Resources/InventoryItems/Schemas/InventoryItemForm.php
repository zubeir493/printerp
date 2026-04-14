<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sku')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_good' => 'Finished Good',
                    ])
                    ->required()
                    ->default('raw_material')
                    ->live(),
                TextInput::make('price')
                    ->label('Price / Value')
                    ->helperText('Selling price for finished goods, or stock value per unit for raw materials.')
                    ->numeric()
                    ->required()
                    ->suffix('Birr'),
                TextInput::make('unit')
                    ->label('Base Unit')
                    ->required(),
                TextInput::make('purchase_unit')
                    ->label('Purchase Unit')
                    ->hidden(fn($get) => $get('type') !== 'raw_material'),
                TextInput::make('conversion_factor')
                    ->numeric()
                    ->hidden(fn($get) => $get('type') !== 'raw_material'),
                Toggle::make('is_sellable')
                    ->label('Is Sellable')
                    ->default(false),
            ]);
    }
}

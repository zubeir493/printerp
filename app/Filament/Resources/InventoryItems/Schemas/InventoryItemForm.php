<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('unit')
                    ->label('Base Unit')
                    ->required(),
                TextInput::make('purchase_unit')
                    ->label('Purchase Unit'),
                TextInput::make('conversion_factor')
                    ->numeric(),
                TextInput::make('average_cost')
                    ->numeric()
                    ->disabled()
                    ->suffix('Birr')
                    ->default(0),
                Select::make('type')
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_good' => 'Finished Good',
                    ])
                    ->required()
                    ->default('raw_material'),
                Toggle::make('is_sellable')
                    ->label('Is Sellable')
                    ->default(false),
            ]);
    }
}

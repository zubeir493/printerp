<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('location'),
                \Filament\Forms\Components\Toggle::make('is_default')
                    ->label('Is Default Warehouse')
                    ->default(false),
            ]);
    }
}

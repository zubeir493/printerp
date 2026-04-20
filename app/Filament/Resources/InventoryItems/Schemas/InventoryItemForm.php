<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Select::make('type')
                        ->options([
                            'raw_material' => 'Raw Material',
                            'finished_good' => 'Finished Good',
                            'tools' => 'Tools',
                            'spare_parts' => 'Spare Parts',
                        ])
                        ->required()
                        ->default('raw_material')
                        ->live(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sku')
                        ->required()
                        ->unique(ignoreRecord: true),
                    TextInput::make('unit')
                        ->label('Base Unit')
                        ->hidden(fn ($get) => in_array($get('type'), ['wip', 'tools', 'spare_parts']))
                        ->required(fn ($get) => !in_array($get('type'), ['wip', 'tools', 'spare_parts'])),
                    TextInput::make('purchase_unit')
                        ->label('Purchase Unit')
                        ->hidden(fn($get) => $get('type') !== 'raw_material'),
                    TextInput::make('conversion_factor')
                        ->numeric()
                        ->hidden(fn($get) => $get('type') !== 'raw_material'),
                    TextInput::make('price')
                        ->label('Price / Value')
                        ->helperText('Selling price for finished goods, or stock value per unit for raw materials.')
                        ->numeric()
                        ->hidden(fn ($get) => in_array($get('type'), ['wip', 'tools', 'spare_parts']))
                        ->required(fn ($get) => !in_array($get('type'), ['wip', 'tools', 'spare_parts']))
                        ->suffix('Birr'),
                    Toggle::make('is_sellable')
                        ->label('Is Sellable')
                        ->hidden(fn($get) => in_array($get('type'), ['wip', 'tools', 'spare_parts']))
                        ->default(false),
                ])->columnSpan(4)->columns(2),
                Group::make([
                    FileUpload::make('image')
                        ->image()
                        ->directory('inventory-items')
                        ->hidden(fn($get) => $get('type') === 'wip'),
                ])->columnSpan(2)
            ])->columns(6);
    }
}

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
                        ->hidden(fn ($get) => in_array($get('type'), ['tools', 'spare_parts']))
                        ->required(fn ($get) => !in_array($get('type'), ['tools', 'spare_parts'])),
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
                        ->hidden(fn ($get) => in_array($get('type'), ['tools', 'spare_parts']))
                        ->required(fn ($get) => !in_array($get('type'), ['tools', 'spare_parts']))
                        ->suffix('Birr'),
                    Toggle::make('is_sellable')
                        ->label('Is Sellable')
                        ->hidden(fn($get) => in_array($get('type'), ['tools', 'spare_parts']))
                        ->default(false),
                ])->columnSpan(4)->columns(2),
                Group::make([
                    \Filament\Forms\Components\Placeholder::make('image_view')
                        ->label('')
                        ->visibleOn('view')
                        ->content(function ($record) {
                            if (!$record || !$record->image) return null;
                            $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($record->image, now()->addMinutes(10));
                            return new \Illuminate\Support\HtmlString('<img src="' . $url . '" class="w-full aspect-square rounded-xl object-cover shadow-sm border" />');
                        }),
                    FileUpload::make('image')
                        ->image()
                        ->imageEditor()
                        ->imageAspectRatio('1:1')
                        ->maxSize(1024)
                        ->disk('s3')
                        ->directory('inventory/items')
                        ->previewable(false)
                        ->hiddenOn('view')
                        ->hidden(fn($get) => $get('type') === 'spare_parts'),
                ])->columnSpan(2)
            ])->columns(6);
    }
}

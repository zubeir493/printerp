<?php

namespace App\Filament\Resources\JobOrderTasks\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class JobOrderTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('job_order_id')
                    ->relationship('jobOrder', 'job_order_number')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_cost')
                    ->required()
                    ->label('Cost')
                    ->numeric()
                    ->prefix('$'),
                Repeater::make('paper')
                    ->label('Paper used for this task')
                    ->table([
                        TableColumn::make('Paper')->alignLeft(),
                        TableColumn::make('Required qty (sheets)')->alignLeft(),
                        TableColumn::make('Reserve qty (sheets)')->alignLeft(),
                    ])
                    ->compact()
                    ->schema([
                        Select::make('inventory_item_id')
                            ->label('Material')
                            ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $item = \App\Models\InventoryItem::find($state);
                                $set('base_unit', $item?->unit);
                            }),
                        TextInput::make('required_quantity')
                            ->numeric()
                            ->required()
                            ->default(0),

                        TextInput::make('reserve_quantity')
                            ->numeric()
                            ->default(0),

                        Hidden::make('base_unit'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->addActionLabel('Add paper')
                    ->cloneable()
                    ->addable(false)
                    ->reorderable(false)
                    ->minItems(1),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
            ]);
    }
}

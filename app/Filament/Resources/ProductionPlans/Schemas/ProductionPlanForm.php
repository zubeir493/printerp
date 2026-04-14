<?php

namespace App\Filament\Resources\ProductionPlans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ProductionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->label('Machines')
                    ->relationship()
                    ->table([
                        TableColumn::make('Machine')->alignLeft(),
                        TableColumn::make('Task')->alignLeft(),
                        TableColumn::make('Planned Qty')->alignLeft(),
                        TableColumn::make('Planned Plates')->alignLeft(),
                        TableColumn::make('Planned Rounds')->alignLeft(),
                    ])
                    ->schema([
                        Select::make('machine_id')
                            ->relationship('machine', 'name')
                            ->unique()
                            ->required(),
                        Select::make('job_order_task_id')
                            ->relationship('jobOrderTask', 'name')
                            ->required(),
                        TextInput::make('planned_quantity')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('planned_plates')
                            ->numeric()
                            ->default(0),
                        TextInput::make('planned_rounds')
                            ->numeric()
                            ->default(0),
                    ])
                    ->compact()
                    ->columns(5)
                    ->defaultItems(1)->columnSpan(5),
                Section::make([
                    DatePicker::make('week_start')
                        ->required(),
                    DatePicker::make('week_end')
                        ->required(),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'approved' => 'Approved',
                        ])
                        ->default('draft')
                        ->required(),
                ])->columnSpan(2),
            ])->columns(7);
    }
}

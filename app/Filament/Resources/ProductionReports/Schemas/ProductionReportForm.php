<?php

namespace App\Filament\Resources\ProductionReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ProductionReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->relationship()
                    ->table([
                        TableColumn::make('Machine')->alignLeft(),
                        TableColumn::make('Task')->alignLeft(),
                        TableColumn::make('Planned Qty')->alignLeft(),
                        TableColumn::make('Actual Qty')->alignLeft(),
                        TableColumn::make('Planned Plates')->alignLeft(),
                        TableColumn::make('Plates Used')->alignLeft(),
                        TableColumn::make('Planned Rounds')->alignLeft(),
                        TableColumn::make('Rounds')->alignLeft(),
                        TableColumn::make('Date')->alignLeft(),
                    ])
                    ->schema([
                        Placeholder::make('machine_name')
                            ->label('Machine')
                            ->color('gray')
                            ->content(fn($record) => $record?->productionPlanItem?->machine?->name),
                        Placeholder::make('job_order_task')
                            ->color('gray')
                            ->label('Task')
                            ->content(fn($record) => $record?->productionPlanItem?->jobOrderTask?->name),
                        Placeholder::make('planned_quantity')
                            ->color('gray')
                            ->label('Planned')
                            ->content(fn($record) => $record?->productionPlanItem?->planned_quantity),
                        TextInput::make('actual_quantity')
                            ->numeric()
                            ->required(),
                        Placeholder::make('planned_plates')
                            ->color('gray')
                            ->label('Planned')
                            ->content(fn($record) => $record?->productionPlanItem?->planned_plates),
                        TextInput::make('plates_used')
                            ->numeric()
                            ->default(0),
                        Placeholder::make('planned_rounds')
                            ->color('gray')
                            ->label('Planned')
                            ->content(fn($record) => $record?->productionPlanItem?->planned_rounds),
                        TextInput::make('rounds')
                            ->numeric()
                            ->default(0),
                        DatePicker::make('date')
                            ->required()
                            ->default(now()),
                    ])
                    ->compact()
                    ->addable(false)
                    ->deletable(false)->columnSpan(7),
                Section::make()
                    ->schema([
                        Select::make('production_plan_id')
                            ->relationship('productionPlan', 'id')
                            ->getOptionLabelFromRecordUsing(fn($record) => "Plan: {$record->week_start->format('M d, Y')} - {$record->week_end->format('M d, Y')}")
                            ->disabled()
                            ->dehydrated(),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                            ])
                            ->default('draft')
                            ->required(),
                    ])->columnSpan(2),
            ])->columns(7);
    }
}

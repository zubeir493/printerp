<?php

namespace App\Filament\Resources\ProductionReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Group;
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
                Repeater::make('machines')
                    ->label('Machine Reports')
                    ->relationship()
                    ->schema([
                        Placeholder::make('machine_name')
                            ->label('Machine')
                            ->content(fn($record) => $record?->productionPlanMachine?->machine?->name),

                        Repeater::make('items')
                            ->label('Production Records')
                            ->relationship()
                            ->schema([
                                Group::make([
                                    Placeholder::make('job_order_task')
                                        ->label('Task')
                                        ->content(fn($record) => $record?->productionPlanItem?->jobOrderTask?->name),
                                    Placeholder::make('planned_quantity')
                                        ->label('Planned Qty')
                                        ->content(fn($record) => $record?->productionPlanItem?->planned_quantity),
                                    TextInput::make('actual_quantity')
                                        ->label('Actual Qty')
                                        ->numeric()
                                        ->required(),
                                ])->columns(3),

                                Group::make([
                                    Placeholder::make('planned_plates')
                                        ->label('Planned Plates')
                                        ->content(fn($record) => $record?->productionPlanItem?->planned_plates),
                                    TextInput::make('plates_used')
                                        ->label('Plates Used')
                                        ->numeric()
                                        ->default(0),
                                    Placeholder::make('planned_rounds')
                                        ->label('Planned Rounds')
                                        ->content(fn($record) => $record?->productionPlanItem?->planned_rounds),
                                    TextInput::make('rounds')
                                        ->label('Actual Rounds')
                                        ->numeric()
                                        ->default(0),
                                    DatePicker::make('date')
                                        ->required()
                                        ->default(now()),
                                ])->columns(5),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->compact()
                            ->columnSpanFull()
                            ->addActionLabel(''),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull()
                    ->addActionLabel(''),
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

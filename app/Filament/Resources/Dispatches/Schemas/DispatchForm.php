<?php

namespace App\Filament\Resources\Dispatches\Schemas;

use App\Models\JobOrderTask;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DispatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Select::make('job_order_id')
                            ->relationship('jobOrder', 'id')
                            ->options(fn() => \App\Models\JobOrder::pluck('job_order_number', 'id'))
                            ->searchable()
                            ->live()
                            ->required(),
                        DatePicker::make('delivery_date')
                            ->default(now())
                            ->required(),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ])->columnSpan(3),
                Section::make('Items to Deliver')
                    ->schema(fn (callable $get) =>
                        collect(JobOrderTask::where('job_order_id', $get('job_order_id'))->get())
                            ->map(fn ($task) =>
                                TextInput::make("quantities.{$task->id}")
                                    ->label($task->name . " ({$task->remaining_quantity} remaining)")
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue($task->remaining_quantity)
                            )
                            ->values()
                            ->all()
                    )->columnSpan(2)
            ])->columns(5);
    }
}

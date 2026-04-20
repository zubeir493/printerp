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
                        Select::make('warehouse_id')
                            ->label('Dispatch From Warehouse')
                            ->options(\App\Models\Warehouse::pluck('name', 'id'))
                            ->default(fn () => \App\Models\Warehouse::where('is_default', true)->value('id'))
                            ->required()
                            ->searchable(),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ])->columnSpan(3),
                Section::make('Delivered Items')
                    ->schema(fn (callable $get, ?\App\Models\Dispatch $record) =>
                        collect(JobOrderTask::where('job_order_id', $get('job_order_id'))->get())
                            ->map(function ($task) use ($record) {
                                $dispatchedQty = 0;
                                if ($record) {
                                    $item = $record->dispatchItems()->where('job_order_task_id', $task->id)->first();
                                    if ($item) {
                                        $dispatchedQty = $item->quantity;
                                    }
                                }

                                return TextInput::make("quantities.{$task->id}")
                                    ->label($task->name)
                                    ->numeric()
                                    ->default($dispatchedQty)
                                    ->formatStateUsing(fn ($state) => $state ?? $dispatchedQty)
                                    ->minValue(0);
                            })
                            ->values()
                            ->all()
                    )->columnSpan(2)
            ])->columns(5);
    }
}

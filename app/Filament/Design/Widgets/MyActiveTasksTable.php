<?php

namespace App\Filament\Design\Widgets;

use App\Models\JobOrderTask;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MyActiveTasksTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'My Active Tasks';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobOrderTask::query()
                    ->with(['jobOrder.partner'])
                    ->where('designer_id', auth()->id())
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->orderBy('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('jobOrder.job_order_number')
                    ->label('Job Order')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobOrder.partner.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Task')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Queued')
                    ->since(),
            ]);
    }
}

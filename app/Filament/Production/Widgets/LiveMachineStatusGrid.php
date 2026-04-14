<?php

namespace App\Filament\Production\Widgets;

use App\Models\Machine;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LiveMachineStatusGrid extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Live Machine Status';
    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(Machine::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Machine')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(function (Machine $record) {
                        $hasLogToday = \App\Models\ProductionReportItem::where('machine_id', $record->id)
                            ->whereDate('date', now())
                            ->exists();
                        return $hasLogToday ? 'Running' : 'Idle';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Running' => 'success',
                        'Idle' => 'gray',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('last_output')
                    ->label('Last Output')
                    ->getStateUsing(function (Machine $record) {
                        return \App\Models\ProductionReportItem::where('machine_id', $record->id)
                            ->latest()
                            ->value('actual_quantity') ?? 0;
                    })
                    ->suffix(' Units'),
            ])
            ->paginated(false);
    }
}

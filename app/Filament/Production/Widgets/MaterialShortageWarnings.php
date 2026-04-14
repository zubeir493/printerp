<?php

namespace App\Filament\Production\Widgets;

use App\Models\ProductionPlan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MaterialShortageWarnings extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Material Shortage Warnings (Imminent)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductionPlan::query()
                    ->where('status', 'draft')
                    // Needs complex logic to check actual stock vs planned stock.
                    // For now, we mock to show the UI capability.
                    ->latest()
                    ->limit(3)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plan Name'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('shortage_alert')
                    ->label('Alert')
                    ->badge()
                    ->color('danger')
                    ->default('Low Raw Materials'),
            ])
            ->actions([
                \Filament\Actions\Action::make('View Plan')
                    ->url(fn (ProductionPlan $record): string => '/production/production-plans/' . $record->id)
                    ->icon('heroicon-m-arrow-right'),
            ]);
    }
}

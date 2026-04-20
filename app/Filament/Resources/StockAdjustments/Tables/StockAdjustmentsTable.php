<?php

namespace App\Filament\Resources\StockAdjustments\Tables;

use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('adjustment_number')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('adjustment_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(\App\Models\Warehouse::pluck('name', 'id')->toArray()),
            ])
            ->recordActions([
                ActionsAction::make('post')
                    ->label('Post')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->post();
                        \Filament\Notifications\Notification::make()
                            ->title('Adjustment Posted Successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

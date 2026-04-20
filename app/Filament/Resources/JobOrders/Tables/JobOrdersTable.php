<?php

namespace App\Filament\Resources\JobOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JobOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('job_order_number')
                    ->label('Job Order')
                    ->description(fn ($record) => $record->partner?->name)
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'design' => 'info',
                        'production' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('tasks_summary')
                    ->label('Tasks')
                    ->getStateUsing(fn ($record) => $record->jobOrderTasks()->where('status', 'completed')->count() . ' / ' . $record->jobOrderTasks()->count())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('total_price')
                    ->money('ETB')
                    ->sortable(),
                \Filament\Tables\Columns\IconColumn::make('advance_paid')
                    ->boolean()
                    ->label('Paid')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                // TextColumn::make('balance')
                //     ->label('Balance')
                //     ->state(fn($record) => $record->balance)
                //     ->suffix(' Birr')
                //     ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                //     ->sortable(query: function ($query, string $direction) {
                //         return $query->selectRaw('*, (total_price - (SELECT COALESCE(SUM(allocated_amount), 0) FROM payment_allocations WHERE allocatable_id = job_orders.id AND allocatable_type = "App\\\Models\\\JobOrder")) as current_balance')
                //             ->orderBy('current_balance', $direction);
                //     }),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\JobOrderExporter::class)
            ])
            ->filters([
                TernaryFilter::make('payment_status')
                    ->label('Payment Status')
                    ->placeholder('All')
                    ->trueLabel('Pending Payments')
                    ->falseLabel('Fully Paid')
                    ->queries(
                        true: fn($query) => $query->pendingPayment(),
                        false: fn($query) => $query->fullyPaid(),
                    ),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'design' => 'Design',
                        'production' => 'Production',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->preload()
                    ->searchable(),

            ])
            ->actions([
                EditAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\JobOrderExporter::class)
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\JobOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
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
                    ->description(fn($record) => $record->partner?->name)
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('tasks_summary')
                    ->label('Tasks')
                    ->getStateUsing(fn($record) => $record->jobOrderTasks()->where('status', 'completed')->count() . ' / ' . $record->jobOrderTasks()->count())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('materials_completion')
                    ->label('Materials Issued')
                    ->state(fn($record) => round($record->materialsCompletionPercentage(), 0) . '%')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        (int)$state >= 100 => 'success',
                        (int)$state >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('total_price')
                    ->suffix(' birr')
                    ->sortable(),
                IconColumn::make('advance_paid')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->paymentAllocations()->exists())
                    ->label('Adv. Paid'),
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
                        'active' => 'Active',
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

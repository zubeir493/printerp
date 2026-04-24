<?php

namespace App\Filament\Resources\JobOrders\Tables;

use App\Filament\Support\PanelAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                    ->color(fn(?string $state): string => match ($state) {
                        'active' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('submission_date')
                    ->label('Submission Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->submission_date && $record->submission_date->isBefore(today()) && !in_array($record->status, ['completed', 'cancelled']) ? 'danger' : null)
                    ->description(fn ($record) => $record->submission_date && $record->submission_date->isBefore(today()) && !in_array($record->status, ['completed', 'cancelled']) ? 'Late' : null),
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
                    ->visible(fn () => PanelAccess::canSeeMoneyValues())
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
                Filter::make('late_jobs')
                    ->label('Late Job Orders')
                    ->query(fn ($query) => $query->late())
                    ->toggle(),
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

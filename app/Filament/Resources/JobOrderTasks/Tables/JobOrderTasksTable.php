<?php

namespace App\Filament\Resources\JobOrderTasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JobOrderTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jobOrder.job_order_number')
                    ->label('Job Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jobOrder.partner.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Task Name')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Cost')
                    ->money()
                    ->sortable(),
                \Filament\Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'design' => 'Design',
                        'production' => 'Production',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->sortable()
                    ->searchable(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\JobOrderTaskExporter::class)
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\JobOrderTaskExporter::class)
                ]),
            ]);
    }
}

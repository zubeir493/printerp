<?php

namespace App\Filament\Resources\JobOrders\Tables;

use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JobOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('job_order_number')
                    ->label('Job Order #')
                    ->searchable(),
                TextColumn::make('job_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('submission_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('total_price')
                    ->suffix(' Birr')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

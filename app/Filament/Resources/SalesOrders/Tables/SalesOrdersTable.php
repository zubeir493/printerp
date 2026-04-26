<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Sales Order')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('partner.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->toggleable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_mode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                    ->color(fn ($state) => $state === 'cash' ? 'success' : 'warning'),
                TextColumn::make('total')
                    ->suffix(' Birr')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->state(fn ($record) => number_format($record->paid_amount, 2) . ' Birr')
                    ->color('success'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->state(fn ($record) => number_format($record->balance, 2) . ' Birr')
                    ->color(fn ($record) => $record->balance > 0 ? 'warning' : 'success'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'void' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Completed',
                        'void' => 'Void',
                    ]),
                SelectFilter::make('payment_mode')
                    ->label('Payment Type')
                    ->options([
                        'cash' => 'Cash',
                        'credit' => 'Credit',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(\App\Models\Warehouse::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\SalesOrderExporter::class),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\SalesOrderExporter::class),
                ]),
            ]);
    }
}

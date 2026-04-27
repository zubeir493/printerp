<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('Payment')
                    ->description(fn ($record) => $record->partner->name)
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(function ($state, $record) {
                        $prefix = $record->direction === 'inbound' ? '+' : '-';
                        return $prefix . number_format($state, 2);
                    })
                    ->description(fn ($record) => 'via ' . ucfirst($record->method))
                    ->color(fn ($record) => $record->direction === 'inbound' ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('transaction_type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(function ($state) {
                        return \App\Enums\PaymentTransactionType::tryFrom($state)?->label() ?? ucwords(str_replace('_', ' ', (string) $state));
                    })
                    ->color('primary'),
                TextColumn::make('voided_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Voided' : 'Active')
                    ->color(fn ($state) => $state ? 'gray' : 'success'),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PaymentExporter::class)
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ]),
                SelectFilter::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank Transfer',
                        'check' => 'Check',
                    ]),
            ])
            ->actions([
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PaymentExporter::class)
                ]),
            ]);
    }
}

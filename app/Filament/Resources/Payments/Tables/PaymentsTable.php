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
                    ->searchable(),
                TextColumn::make('transaction_type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(function ($state) {
                        return \App\Enums\PaymentTransactionType::tryFrom($state)?->label() ?? ucwords(str_replace('_', ' ', (string) $state));
                    })
                    ->color('primary'),
                TextColumn::make('partner.name')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('account.name')
                    ->label('Source Account')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('direction')
                    ->badge()
                    ->color(fn($state) => $state === 'inbound' ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable(),
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('voided_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Voided' : 'Active')
                    ->color(fn ($state) => $state ? 'gray' : 'success')
                    ->toggleable(),
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

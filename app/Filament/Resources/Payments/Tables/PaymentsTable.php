<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->searchable(),
                TextColumn::make('partner.name')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('direction')
                    ->badge()
                    ->color(fn($state) => $state === 'inbound' ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable(),
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PaymentExporter::class)
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
                        ->exporter(\App\Filament\Exports\PaymentExporter::class)
                ]),
            ]);
    }
}

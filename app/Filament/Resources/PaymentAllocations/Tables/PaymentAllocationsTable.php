<?php

namespace App\Filament\Resources\PaymentAllocations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentAllocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment.payment_number')
                    ->label('Payment #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment.partner.name')
                    ->label('Partner')
                    ->searchable(),
                TextColumn::make('payment.direction')
                    ->label('Direction')
                    ->badge()
                    ->color(fn($state) => $state === 'inbound' ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('allocatable_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->badge()
                    ->searchable(),
                TextColumn::make('document_number')
                    ->label('Document #')
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('allocated_amount')
                    ->label('Amount')
                    ->suffix(' Birr')
                    ->sortable(),
                TextColumn::make('payment.payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PaymentAllocationExporter::class)
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('payment_direction')
                    ->label('Direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ])
                    ->query(function ($query, $direction) {
                        if (is_array($direction)) {
                            return $query;
                        }

                        return $direction
                            ? $query->whereHas('payment', fn($query) => $query->where('direction', $direction))
                            : $query;
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PaymentAllocationExporter::class)
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Partners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\Support\PanelAccess;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Partner')
                    ->description(fn ($record) => $record->phone)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->is_customer && $record->is_supplier) return 'Both';
                        return $record->is_customer ? 'Customer' : 'Supplier';
                    })
                    ->color(fn ($state) => match ($state) {
                        'Customer' => 'success',
                        'Supplier' => 'info',
                        'Both' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('partner_type')
                    ->label('Type')
                    ->options([
                        'supplier' => 'Supplier',
                        'customer' => 'Customer',
                    ])
                    ->query(function ($query, $state) {
                        if (is_array($state)) {
                            return $query;
                        }

                        return $state === 'supplier'
                            ? $query->where('is_supplier', true)
                            : ($state === 'customer' ? $query->where('is_customer', true) : $query);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => PanelAccess::canManagePartners()),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PartnerExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => PanelAccess::canManagePartners()),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PartnerExporter::class)
                        ->visible(fn () => PanelAccess::canManagePartners())
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Partners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                IconColumn::make('is_supplier')
                    ->boolean(),
                IconColumn::make('is_customer')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PartnerExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PartnerExporter::class)
                ]),
            ]);
    }
}

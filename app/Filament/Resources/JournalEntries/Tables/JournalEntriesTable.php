<?php

namespace App\Filament\Resources\JournalEntries\Tables;

use Dom\Text;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('total_debit')->suffix(' Birr')
                    ->label('Transferred Amount'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->icon(fn(string $state): string => match ($state) {
                        'draft' => 'heroicon-o-pencil',
                        'posted' => 'heroicon-o-check-circle',
                        'void' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        'void' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\JournalEntryExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\JournalEntryExporter::class)
                ]),
            ]);
    }
}

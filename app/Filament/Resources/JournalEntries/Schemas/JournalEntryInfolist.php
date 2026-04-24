<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class JournalEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('reference'),
                TextEntry::make('narration')
                    ->placeholder('-'),
                TextEntry::make('transaction_type'),
                TextEntry::make('source_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('voided_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('reversal_of_journal_entry_id')
                    ->label('Reversal Of')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

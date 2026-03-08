<?php

namespace App\Filament\Resources\Dispatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DispatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('jobOrder.id')
                    ->label('Job order'),
                TextEntry::make('delivery_date')
                    ->date(),
                TextEntry::make('remarks')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Artworks\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ArtworkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('jobOrder.id')
                    ->label('Job order'),
                TextEntry::make('filename'),
                IconEntry::make('is_approved')
                    ->boolean(),
                TextEntry::make('uploaded_by')
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

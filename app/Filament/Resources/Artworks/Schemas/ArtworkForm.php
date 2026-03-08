<?php

namespace App\Filament\Resources\Artworks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArtworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('job_order_id')
                    ->relationship('jobOrder', 'id')
                    ->required(),
                TextInput::make('filename')
                    ->required(),
                Toggle::make('is_approved')
                    ->required(),
                TextInput::make('uploaded_by')
                    ->numeric(),
            ]);
    }
}

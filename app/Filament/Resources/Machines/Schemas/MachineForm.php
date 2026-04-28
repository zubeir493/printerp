<?php

namespace App\Filament\Resources\Machines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MachineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->maxLength(255),
                TextInput::make('baseline_rounds_per_week')
                    ->label('Baseline Rounds/Week')
                    ->numeric()
                    ->default(0)
                    ->helperText('Expected number of rounds per week for this machine'),
            ]);
    }
}

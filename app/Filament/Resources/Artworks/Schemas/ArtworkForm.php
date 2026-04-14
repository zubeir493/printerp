<?php

namespace App\Filament\Resources\Artworks\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ArtworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Creative Asset')
                    ->description('Link artwork to a Job Order and upload the design file.')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                Select::make('job_order_id')
                                    ->relationship('jobOrder', 'job_order_number')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),
                                Toggle::make('is_approved')
                                    ->label('Production Ready')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->columnSpan(1),
                            ]),
                        \Filament\Forms\Components\FileUpload::make('filename')
                            ->label('Artwork File')
                            ->directory('artworks')
                            ->image()
                            ->imageEditor()
                            ->previewable(true)
                            ->required()
                            ->columnSpanFull(),
                        Hidden::make('uploaded_by')
                            ->default(fn() => Auth::id()),
                    ])
            ]);
    }
}

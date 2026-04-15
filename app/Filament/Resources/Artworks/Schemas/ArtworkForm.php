<?php

namespace App\Filament\Resources\Artworks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
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
                        Flex::make([
                                FileUpload::make('filename')
                                    ->label('Artwork File')
                                    ->disk('s3')
                                    ->directory('artworks')
                                    ->preserveFilenames()
                                    ->maxSize(51200)
                                    ->previewable(false)
                                    ->required()
                                    ->columnSpanFull(),
                                Placeholder::make('download_link')
                                    ->label('')
                                    ->content(function ($record) {
                                        if (!$record) return null;
                                        $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($record->filename, now()->addMinutes(60));
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="p-4 bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center">' .
                                            '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 font-medium flex items-center gap-2">' .
                                            'Click to Download' .
                                            '</a>' .
                                            '</div>'
                                        );
                                    })
                            ]),
                        Hidden::make('uploaded_by')
                            ->default(fn() => Auth::id()),
                        
                    ]),
            ]);
    }
}

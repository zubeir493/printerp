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
                \Filament\Schemas\Components\Section::make('Artwork Overview')
                    ->schema([
                        \Filament\Schemas\Components\Flex::make([
                            \Filament\Schemas\Components\Grid::make(2)
                                ->schema([
                                    TextEntry::make('jobOrder.job_order_number')
                                        ->label('Job Order')
                                        ->weight('bold')
                                        ->color('primary'),
                                    IconEntry::make('is_approved')
                                        ->label('Production Approved')
                                        ->boolean()
                                        ->trueColor('success')
                                        ->falseColor('warning'),
                                    TextEntry::make('uploader.name')
                                        ->label('Uploaded By')
                                        ->icon('heroicon-m-user'),
                                    TextEntry::make('created_at')
                                        ->label('Uploaded Date')
                                        ->dateTime(),
                                ]),
                            \Filament\Infolists\Components\ImageEntry::make('filename')
                                ->hiddenLabel()
                                ->height(200)
                                ->visible(fn($record) => in_array(strtolower(pathinfo($record->filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']))
                                ->extraAttributes(['class' => 'rounded-xl shadow-lg border border-gray-100']),

                            \Filament\Infolists\Components\TextEntry::make('download')
                                ->label('File Source')
                                ->default('Download Artwork')
                                ->icon('heroicon-m-arrow-down-tray')
                                ->color('primary')
                                ->url(fn($record) => \Illuminate\Support\Facades\Storage::url($record->filename), true)
                                ->visible(fn($record) => !in_array(strtolower(pathinfo($record->filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']))
                                ->extraAttributes(['class' => 'p-4 bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center']),
                        ])->from('md')
                    ])
            ]);
    }
}

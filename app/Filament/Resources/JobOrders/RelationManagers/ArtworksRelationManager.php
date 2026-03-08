<?php

namespace App\Filament\Resources\JobOrders\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArtworksRelationManager extends RelationManager
{
    protected static string $relationship = 'artworks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\FileUpload::make('filename')
                    ->label('Artwork File')
                    ->directory('artworks')
                    ->required(),
                \Filament\Forms\Components\Toggle::make('is_approved')
                    ->label('Is Approved')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('filename')
                    ->label('Artwork')
                    ->circular(),
                \Filament\Tables\Columns\TextColumn::make('filename')
                    ->label('File Name')
                    ->searchable(),
                \Filament\Tables\Columns\ToggleColumn::make('is_approved')
                    ->label('Approved'),
            ])
            ->searchable(false)
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

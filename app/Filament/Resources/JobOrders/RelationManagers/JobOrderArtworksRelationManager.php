<?php

namespace App\Filament\Resources\JobOrders\RelationManagers;

use App\Models\Artwork;
use App\Models\JobOrderTask;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JobOrderArtworksRelationManager extends RelationManager
{
    protected static string $relationship = 'artworks';

    protected static ?string $title = 'Artworks Overview';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Artwork Details')
                    ->schema([
                        Select::make('job_order_task_id')
                            ->label('Task')
                            ->options(fn ($record) => $this->getOwnerRecord()->jobOrderTasks->pluck('name', 'id'))
                            ->required(),
                        FileUpload::make('filename')
                            ->label('Artwork File')
                            ->disk('s3')
                            ->directory('artworks')
                            ->preserveFilenames()
                            ->maxSize(51200)
                            ->image()
                            ->imageEditor()
                            ->required(),
                        \Filament\Forms\Components\Toggle::make('is_approved')
                            ->label('Approved'),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('jobOrderTask.name')
                    ->label('Task'),
                \Filament\Tables\Columns\IconColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => strtolower(pathinfo($record->filename, PATHINFO_EXTENSION)))
                    ->icon(fn ($state) => match ($state) {
                        'pdf' => 'heroicon-s-document-text',
                        'ai', 'eps', 'psd' => 'heroicon-s-paint-brush',
                        'png', 'jpg', 'jpeg', 'webp' => 'heroicon-s-photo',
                        'zip', 'rar' => 'heroicon-s-archive-box',
                        default => 'heroicon-s-document',
                    })
                    ->color(fn ($state) => match ($state) {
                        'pdf' => 'danger',
                        'ai', 'eps', 'psd' => 'warning',
                        'png', 'jpg', 'jpeg', 'webp' => 'success',
                        'zip', 'rar' => 'info',
                        default => 'gray',
                    }),
                \Filament\Tables\Columns\TextColumn::make('filename')
                    ->label('File Name')
                    ->formatStateUsing(fn ($state) => basename($state))
                    ->searchable(),
                \Filament\Tables\Columns\IconColumn::make('is_approved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status'),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->hidden(fn($record) => $record->is_approved)
                    ->action(fn($record) => $record->update(['is_approved' => true])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

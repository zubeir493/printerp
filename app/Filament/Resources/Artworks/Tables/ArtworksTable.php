<?php

namespace App\Filament\Resources\Artworks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArtworksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\IconColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(fn($record) => strtolower(pathinfo($record->filename, PATHINFO_EXTENSION)))
                    ->icon(fn($state) => match ($state) {
                        'pdf' => 'heroicon-s-document-text',
                        'ai', 'eps', 'psd' => 'heroicon-s-paint-brush',
                        'png', 'jpg', 'jpeg', 'webp' => 'heroicon-s-photo',
                        default => 'heroicon-s-document',
                    })
                    ->color(fn($state) => match ($state) {
                        'pdf' => 'danger',
                        'ai', 'eps', 'psd' => 'warning',
                        'png', 'jpg', 'jpeg', 'webp' => 'success',
                        default => 'gray',
                    })
                    ->size(\Filament\Support\Enums\IconSize::Large),
                TextColumn::make('jobOrderTask.name')
                    ->label('Task')
                    ->description(fn ($record) => $record->jobOrder?->job_order_number)
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('filename')
                    ->label('File Name')
                    ->formatStateUsing(fn ($state) => basename($state))
                    ->description(fn($record) => $record->uploader?->name ? "Uploaded by {$record->uploader->name}" : 'System')
                    ->searchable(),
                IconColumn::make('is_approved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('created_at')
                    ->label('Date Uploaded')
                    ->dateTime()
                    ->since()
                    ->color('gray')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('job_order_task_id')
                    ->label('Filter By Task')
                    ->relationship('jobOrderTask', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('job_order_id')
                    ->label('Filter By Job Order')
                    ->options(\App\Models\JobOrder::pluck('job_order_number', 'id'))
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('jobOrderTask', fn($q) => $q->where('job_order_id', $data['value']));
                        }
                    })
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->hidden(fn($record) => $record->is_approved)
                    ->action(fn($record) => $record->update(['is_approved' => true])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

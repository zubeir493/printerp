<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

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

class GoodsReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'goodsReceipts';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('receipt_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('receipt_number')
            ->columns([
                TextColumn::make('receipt_number')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->searchable(),
                TextColumn::make('receipt_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                        default => 'primary',
                    })
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}

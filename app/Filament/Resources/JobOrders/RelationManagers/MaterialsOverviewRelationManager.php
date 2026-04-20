<?php

namespace App\Filament\Resources\JobOrders\RelationManagers;

use App\Models\InventoryItem;
use App\Models\JobOrder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MaterialsOverviewRelationManager extends RelationManager
{
    protected static string $relationship = 'materialRequests';

    protected static ?string $title = 'Materials Overview';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jobOrderTask.name')
                    ->label('Task Name')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('inventoryItem.name')
                    ->label('Raw Material')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('required_quantity')
                    ->label('Required Qty')
                    ->numeric(2),

                Tables\Columns\TextColumn::make('requested_quantity')
                    ->label('Requested Qty')
                    ->numeric(2),

                Tables\Columns\TextColumn::make('issued_quantity')
                    ->label('Issued Qty')
                    ->numeric(2),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}

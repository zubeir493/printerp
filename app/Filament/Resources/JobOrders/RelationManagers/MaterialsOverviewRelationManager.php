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
    protected static string $relationship = 'materialMovements'; // We use this as a base relationship

    protected static ?string $title = 'Materials Overview';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                /** @var JobOrder $jobOrder */
                $jobOrder = $this->getOwnerRecord();
                
                // Get unique IDs of all materials required in tasks OR issued in movements
                $requiredIds = $jobOrder->jobOrderTasks->flatMap->paper->pluck('inventory_item_id')->unique();
                $movementIds = $jobOrder->materialMovements()->pluck('inventory_item_id')->unique();
                
                $allIds = $requiredIds->concat($movementIds)->unique()->filter();

                return InventoryItem::query()->whereIn('id', $allIds);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Material')
                    ->weight('bold')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('required_quantity')
                    ->label('Required')
                    ->alignCenter()
                    ->state(function (InventoryItem $record) {
                        $jobOrder = $this->getOwnerRecord();
                        return (float) $jobOrder->jobOrderTasks->flatMap->paper
                            ->where('inventory_item_id', $record->id)
                            ->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));
                    })
                    ->numeric(2),

                Tables\Columns\TextColumn::make('issued_quantity')
                    ->label('Issued')
                    ->alignCenter()
                    ->state(function (InventoryItem $record) {
                        $jobOrder = $this->getOwnerRecord();
                        return (float) $jobOrder->issuedQuantityFor($record->id);
                    })
                    ->numeric(2),

                Tables\Columns\TextColumn::make('remaining')
                    ->label('Remaining')
                    ->alignCenter()
                    ->state(function (InventoryItem $record) {
                        $jobOrder = $this->getOwnerRecord();
                        $required = (float) $jobOrder->jobOrderTasks->flatMap->paper
                            ->where('inventory_item_id', $record->id)
                            ->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));
                        $issued = (float) $jobOrder->issuedQuantityFor($record->id);
                        
                        return max(0, $required - $issued);
                    })
                    ->numeric(2)
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('overconsumed')
                    ->label('Overconsumed')
                    ->alignCenter()
                    ->state(function (InventoryItem $record) {
                        $jobOrder = $this->getOwnerRecord();
                        return (float) $jobOrder->overConsumedQuantity($record->id);
                    })
                    ->numeric(2)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('completion')
                    ->label('Completion')
                    ->state(function (InventoryItem $record) {
                        $jobOrder = $this->getOwnerRecord();
                        $required = (float) $jobOrder->jobOrderTasks->flatMap->paper
                            ->where('inventory_item_id', $record->id)
                            ->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));
                        $issued = (float) $jobOrder->issuedQuantityFor($record->id);
                        
                        if ($required <= 0) return 100;
                        return round(min(100, ($issued / $required) * 100), 1);
                    })
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state > 0 ? 'warning' : 'gray')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Read-only overview
            ])
            ->actions([
                // No individual actions on summary lines
            ])
            ->bulkActions([
                //
            ]);
    }
}

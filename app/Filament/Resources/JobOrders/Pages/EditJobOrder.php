<?php

namespace App\Filament\Resources\JobOrders\Pages;

use App\Filament\Resources\JobOrders\JobOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditJobOrder extends EditRecord
{
    protected static string $resource = JobOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            \Filament\Actions\Action::make('issue_materials')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('warning')
                ->visible(fn() => $this->record->status === 'production')
                ->form(fn($record) => [
                    Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->options(\App\Models\Warehouse::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    Repeater::make('items')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Select::make('inventory_item_id')
                                ->label('Material')
                                ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->label('Quantity to Issue')
                                ->hint(fn($get) => "Required: " . ($record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $get('inventory_item_id'))->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0))) . " | Issued: " . $record->issuedQuantityFor($get('inventory_item_id')))
                        ])->columns(2)
                        ->default(fn() => $record->jobOrderTasks->flatMap->paper->pluck('inventory_item_id')->unique()->map(fn($itemId) => [
                            'inventory_item_id' => (int)$itemId,
                            'quantity' => (float)max(0, (($record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $itemId)->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0))) - $record->issuedQuantityFor($itemId))),
                        ])->values()->toArray()),
                ])
                ->modalHeading('Issue Materials')
                ->requiresConfirmation(fn($data) => collect($data['items'] ?? [])->contains(function ($item) {
                    $required = (float) $this->record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $item['inventory_item_id'])->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));
                    $issued = (float) $this->record->issuedQuantityFor($item['inventory_item_id']);
                    return ($item['quantity'] ?? 0) > 0 && round($issued + ($item['quantity'] ?? 0), 4) > round($required, 4);
                }))
                ->modalDescription(fn($data) => collect($data['items'] ?? [])->contains(function ($item) {
                    $required = (float) $this->record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $item['inventory_item_id'])->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));
                    $issued = (float) $this->record->issuedQuantityFor($item['inventory_item_id']);
                    return ($item['quantity'] ?? 0) > 0 && round($issued + ($item['quantity'] ?? 0), 4) > round($required, 4);
                }) ? 'You are issuing more than the required quantity. Do you want to proceed?' : null)
                ->action(function ($record, $data) {
                    $inventoryService = app(\App\Services\InventoryService::class);
                    foreach ($data['items'] as $item) {
                        $inventoryService->consumeStock(
                            $item['inventory_item_id'],
                            $data['warehouse_id'],
                            $item['quantity'],
                            'consumption',
                            $record->id
                        );
                    }

                    if ($record->materialsCompletionPercentage() >= 100) {
                        $record->update(['materials_fully_issued_at' => now()]);
                    }
                }),
            \Filament\Actions\Action::make('return_materials')
                ->label('Return Materials')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn() => $this->record->status === 'production')
                ->form(fn($record) => [
                    Repeater::make('warehouses')
                        ->label('Issues by Warehouse')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('warehouse_id'),
                            \Filament\Forms\Components\Placeholder::make('warehouse_name')
                                ->label(fn($get) => \App\Models\Warehouse::find($get('warehouse_id'))?->name ?? 'Unknown Warehouse'),
                            Repeater::make('items')
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    \Filament\Forms\Components\Select::make('inventory_item_id')
                                        ->label('Material')
                                        ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                        ->disabled()
                                        ->dehydrated(),
                                    \Filament\Forms\Components\TextInput::make('quantity')
                                        ->numeric()
                                        ->required()
                                        ->label('Quantity to Return')
                                        ->hint(fn($get) => "Available: " . abs($get('net_quantity') ?? 0))
                                        ->maxValue(fn($get) => abs($get('net_quantity') ?? 0)),
                                    \Filament\Forms\Components\Hidden::make('net_quantity'),
                                ])->columns(2)
                        ])
                        ->default(function () use ($record) {
                            $balances = $record->issuedBalanceByWarehouse();
                            return $balances->map(fn($items, $warehouseId) => [
                                'warehouse_id' => $warehouseId,
                                'items' => $items->map(fn($m) => [
                                    'inventory_item_id' => $m->inventory_item_id,
                                    'net_quantity' => $m->net_quantity,
                                    'quantity' => 0,
                                ])->filter(fn($i) => $i['net_quantity'] < 0)->values()->toArray()
                            ])->filter(fn($w) => count($w['items']) > 0)->values()->toArray();
                        })
                ])
                ->action(function ($record, $data) {
                    $inventoryService = app(\App\Services\InventoryService::class);
                    foreach ($data['warehouses'] as $warehouseGroup) {
                        foreach ($warehouseGroup['items'] as $item) {
                            if (($item['quantity'] ?? 0) <= 0) continue;

                            $inventoryService->receiveStock(
                                $item['inventory_item_id'],
                                $warehouseGroup['warehouse_id'],
                                $item['quantity'],
                                \App\Models\InventoryItem::find($item['inventory_item_id'])->price,
                                'material_return',
                                $record->id
                            );
                        }
                    }
                }),
        ];
    }
}

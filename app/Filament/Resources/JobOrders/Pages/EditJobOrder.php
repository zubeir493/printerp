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
            \Filament\Actions\Action::make('issue_materials')
                ->label('Issue Materials')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->visible(
                    fn($record) =>
                    !in_array($record->status, ['completed', 'cancelled']) &&
                        $record->materialRequests()
                        ->whereColumn('issued_quantity', '<', 'requested_quantity')
                        ->whereHas('jobOrderTask', fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
                        ->exists()
                )
                ->form(fn($record) => [
                    \Filament\Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->options(\App\Models\Warehouse::pluck('name', 'id'))
                        ->default(fn() => \App\Models\Warehouse::where('is_default', true)->value('id'))
                        ->required()
                        ->searchable()
                        ->live(),
                    \Filament\Forms\Components\Repeater::make('items')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('material_request_id'),
                            \Filament\Forms\Components\Select::make('inventory_item_id')
                                ->label('Material')
                                ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                ->disabled()
                                ->dehydrated(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->label('Quantity to Issue')
                                ->hint(function ($get, $record) {
                                    $pending = $record->materialRequests->find($get('material_request_id'))?->requested_quantity - $record->materialRequests->find($get('material_request_id'))?->issued_quantity;
                                    $warehouseId = $get('../../warehouse_id');
                                    $itemId = $get('inventory_item_id');
                                    $stock = $warehouseId ? \App\Models\InventoryBalance::where('warehouse_id', $warehouseId)->where('inventory_item_id', $itemId)->value('quantity_on_hand') ?? 0 : 0;
                                    return "Pending: {$pending} | In Stock: {$stock}";
                                })
                                ->maxValue(function ($get, $record) {
                                    $pending = $record->materialRequests->find($get('material_request_id'))?->requested_quantity - $record->materialRequests->find($get('material_request_id'))?->issued_quantity;
                                    $warehouseId = $get('../../warehouse_id');
                                    $itemId = $get('inventory_item_id');
                                    $stock = $warehouseId ? \App\Models\InventoryBalance::where('warehouse_id', $warehouseId)->where('inventory_item_id', $itemId)->value('quantity_on_hand') ?? 0 : 0;
                                    return min($pending, $stock);
                                })
                        ])->columns(2)
                        ->default(fn() => $record->materialRequests()
                            ->whereColumn('issued_quantity', '<', 'requested_quantity')
                            ->whereHas('jobOrderTask', fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
                            ->get()
                            ->map(fn($mr) => [
                                'material_request_id' => $mr->id,
                                'inventory_item_id' => $mr->inventory_item_id,
                                'quantity' => $mr->requested_quantity - $mr->issued_quantity,
                            ])->toArray()),
                ])
                ->action(function ($record, $data) {
                    try {
                        $inventoryService = app(\App\Services\InventoryService::class);
                        \DB::beginTransaction();
                        foreach ($data['items'] as $item) {
                            if ($item['quantity'] <= 0) continue;

                            $mr = \App\Models\MaterialRequest::find($item['material_request_id']);

                            $stock = \App\Models\InventoryBalance::where('warehouse_id', $data['warehouse_id'])
                                ->where('inventory_item_id', $mr->inventory_item_id)
                                ->value('quantity_on_hand') ?? 0;

                            if ($stock < $item['quantity']) {
                                throw new \Exception("Insufficient stock for {$mr->inventoryItem->name} in the selected warehouse.");
                            }

                            $inventoryService->consumeStock(
                                $mr->inventory_item_id,
                                $data['warehouse_id'],
                                $item['quantity'],
                                'consumption',
                                $record->id
                            );
                            $mr->increment('issued_quantity', $item['quantity']);
                        }
                        \DB::commit();

                        \Filament\Notifications\Notification::make()
                            ->title('Materials Issued Successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \DB::rollBack();
                        \Filament\Notifications\Notification::make()
                            ->title('Error Issuing Materials')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            \Filament\Actions\Action::make('return_materials')
                ->label('Return Materials')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(
                    fn($record) =>
                    $record->status !== 'completed' &&
                        $record->materialRequests()
                        ->where('issued_quantity', '>', 0)
                        ->whereHas('jobOrderTask', fn($q) => $q->where('status', '!=', 'completed'))
                        ->exists()
                )
                ->form(fn($record) => [
                    \Filament\Forms\Components\Repeater::make('items')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('material_request_id'),
                            \Filament\Forms\Components\Hidden::make('original_warehouse_id'),
                            \Filament\Forms\Components\Select::make('inventory_item_id')
                                ->label('Material')
                                ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                ->disabled()
                                ->dehydrated(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->label('Quantity to Return')
                                ->hint(fn($get) => "Issued: " . $record->materialRequests->find($get('material_request_id'))?->issued_quantity)
                                ->maxValue(fn($get) => $record->materialRequests->find($get('material_request_id'))?->issued_quantity)
                        ])->columns(2)
                        ->default(fn() => $record->materialRequests()
                            ->where('issued_quantity', '>', 0)
                            ->whereHas('jobOrderTask', fn($q) => $q->where('status', '!=', 'completed'))
                            ->get()
                            ->map(function ($mr) use ($record) {
                                $originalWarehouse = \App\Models\StockMovement::where('type', 'consumption')
                                    ->where('reference_id', $record->id)
                                    ->where('inventory_item_id', $mr->inventory_item_id)
                                    ->orderByDesc('movement_date')
                                    ->value('warehouse_id');

                                return [
                                    'material_request_id' => $mr->id,
                                    'inventory_item_id' => $mr->inventory_item_id,
                                    'original_warehouse_id' => $originalWarehouse,
                                    'quantity' => 0,
                                ];
                            })->toArray()),
                ])
                ->action(function ($record, $data) {
                    try {
                        $inventoryService = app(\App\Services\InventoryService::class);
                        $defaultWarehouseId = \App\Models\Warehouse::where('is_default', true)->value('id');
                        \DB::beginTransaction();

                        foreach ($data['items'] as $item) {
                            if ($item['quantity'] <= 0) continue;

                            $mr = \App\Models\MaterialRequest::find($item['material_request_id']);

                            if ($item['quantity'] > $mr->issued_quantity) {
                                throw new \Exception("Cannot return more than what was issued for {$mr->inventoryItem->name}.");
                            }

                            // Return to original warehouse if known, else default
                            $returnWarehouseId = $item['original_warehouse_id'] ?? $defaultWarehouseId;

                            $inventoryService->receiveStock(
                                $mr->inventory_item_id,
                                $returnWarehouseId,
                                $item['quantity'],
                                $mr->inventoryItem->price,
                                'material_return',
                                $record->id
                            );
                            $mr->decrement('issued_quantity', $item['quantity']);
                        }
                        \DB::commit();

                        \Filament\Notifications\Notification::make()
                            ->title('Materials Returned Successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \DB::rollBack();
                        \Filament\Notifications\Notification::make()
                            ->title('Error Returning Materials')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}

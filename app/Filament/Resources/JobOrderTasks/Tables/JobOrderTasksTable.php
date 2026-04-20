<?php

namespace App\Filament\Resources\JobOrderTasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JobOrderTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jobOrder.job_order_number')
                    ->label('Job Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Task')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'design' => 'Design',
                        'production' => 'Production',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->sortable()
                    ->searchable(),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\JobOrderTaskExporter::class)
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'design' => 'Design',
                        'production' => 'Production',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('request_materials')
                    ->label('Request Materials')
                    ->icon('heroicon-o-document-plus')
                    ->color('info')
                    ->form(fn ($record) => [
                        \Filament\Forms\Components\Repeater::make('items')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                \Filament\Forms\Components\Select::make('inventory_item_id')
                                    ->label('Material')
                                    ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('requested_quantity')
                                    ->label('Quantity to Request')
                                    ->numeric()
                                    ->required()
                                    ->hint(fn ($get) => "Required: " . ($record->paper[$get('paper_index')]['required_quantity'] ?? 0)),
                                \Filament\Forms\Components\Hidden::make('paper_index'),
                            ])->columns(2)
                            ->default(fn () => collect($record->paper ?? [])->map(fn ($item, $index) => [
                                'inventory_item_id' => $item['inventory_item_id'],
                                'requested_quantity' => ($item['required_quantity'] ?? 0) + ($item['reserve_quantity'] ?? 0),
                                'paper_index' => $index,
                            ])->toArray())
                    ])
                    ->action(function (array $data, $record) {
                        foreach ($data['items'] as $item) {
                            if ($item['requested_quantity'] <= 0) continue;
                            
                            \App\Models\MaterialRequest::create([
                                'job_order_task_id' => $record->id,
                                'inventory_item_id' => $item['inventory_item_id'],
                                'requested_quantity' => $item['requested_quantity'],
                                'required_quantity' => $record->paper[$item['paper_index']]['required_quantity'] ?? 0,
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Materials Requested')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('issue_materials')
                    ->label('Issue Materials')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->visible(fn ($record) => $record->materialRequests()->whereColumn('issued_quantity', '<', 'requested_quantity')->exists())
                    ->form(fn ($record) => [
                        \Filament\Forms\Components\Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->options(\App\Models\Warehouse::pluck('name', 'id'))
                            ->default(fn () => \App\Models\Warehouse::where('is_default', true)->value('id'))
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
                                    ->helperText(function ($get, $record) {
                                        $warehouseId = $get('../../warehouse_id');
                                        if (!$warehouseId) return "Please select a warehouse first.";
                                        return null;
                                    })
                            ])->columns(2)
                            ->default(fn () => $record->materialRequests()
                                ->whereColumn('issued_quantity', '<', 'requested_quantity')
                                ->get()
                                ->map(fn ($mr) => [
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
                                
                                // Double check stock in action to prevent race conditions
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
                                    $record->job_order_id
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\JobOrderTaskExporter::class)
                ]),
            ]);
    }
}

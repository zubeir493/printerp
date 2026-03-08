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
                ->label('Issue Materials')
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
                        ->grid(2)->addable(false)->deletable(false)
                        ->schema([
                            Select::make('inventory_item_id')
                                ->label('Material')
                                ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(
                                    fn($state, $set, $get) =>
                                    $set('remaining', $record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $state)->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0)) - $record->issuedQuantityFor($state))
                                ),
                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->label('Quantity to Issue'),
                            \Filament\Forms\Components\Placeholder::make('info')
                                ->content(fn($get) => "Required: " . ($record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $get('inventory_item_id'))->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0))) . " | Issued: " . $record->issuedQuantityFor($get('inventory_item_id')))
                        ])
                        ->default(fn() => $record->jobOrderTasks->flatMap->paper->map(fn($p) => [
                            'inventory_item_id' => $p['inventory_item_id'],
                            'quantity' => (($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0)) - $record->issuedQuantityFor($p['inventory_item_id']),
                        ])->filter(fn($p) => $p['quantity'] > 0)->values()->toArray())
                ])
                ->requiresConfirmation(fn($data) => collect($data['items'] ?? [])->contains(function ($item) {
                    $required = $this->record->jobOrderTasks->flatMap->paper->where('inventory_item_id', $item['inventory_item_id'])->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));
                    $issued = $this->record->issuedQuantityFor($item['inventory_item_id']);
                    return ($issued + ($item['quantity'] ?? 0)) > $required;
                }))
                ->modalHeading('Confirm Over-issuing')
                ->modalDescription('You are issuing more than the required quantity. Do you want to proceed?')
                ->action(function ($record, $data) {
                    $inventoryService = app(\App\Services\InventoryService::class);
                    foreach ($data['items'] as $item) {
                        $inventoryService->consumeStock(
                            $item['inventory_item_id'],
                            $data['warehouse_id'],
                            $item['quantity'],
                            'material_issue',
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
                    \Filament\Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->options(\App\Models\Warehouse::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\Repeater::make('items')
                        ->schema([
                            \Filament\Forms\Components\Select::make('inventory_item_id')
                                ->label('Material')
                                ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->label('Quantity to Return'),
                        ])
                ])
                ->action(function ($record, $data) {
                    $inventoryService = app(\App\Services\InventoryService::class);
                    foreach ($data['items'] as $item) {
                        $issued = $record->issuedQuantityFor($item['inventory_item_id']);
                        if ($item['quantity'] > $issued) {
                            throw new \Exception("Cannot return more than net issued ({$issued}) for item ID {$item['inventory_item_id']}.");
                        }

                        $inventoryService->receiveStock(
                            $item['inventory_item_id'],
                            $data['warehouse_id'],
                            $item['quantity'],
                            \App\Models\InventoryItem::find($item['inventory_item_id'])->average_cost,
                            'material_return',
                            $record->id
                        );
                    }
                }),
        ];
    }
}

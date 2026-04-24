<?php

namespace App\Filament\Resources\JobOrderTasks\Tables;

use App\Models\User;
use App\Services\MaterialIssueService;
use App\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JobOrderTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Task')
                    ->weight('bold')
                    ->description(fn($record) => $record->jobOrder->job_order_number)
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('designer.name')
                    ->label('Designer')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color(fn ($state) => filled($state) ? 'info' : 'gray')
                    ->searchable(),
                SelectColumn::make('status')
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
                \Filament\Tables\Filters\SelectFilter::make('designer_id')
                    ->label('Designer')
                    ->options(fn () => User::query()
                        ->where('role', UserRole::Design->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\TernaryFilter::make('assigned')
                    ->label('Assignment')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('designer_id'),
                        false: fn ($query) => $query->whereNull('designer_id'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->actions([
                \Filament\Actions\Action::make('assign_designer')
                    ->label('Assign Designer')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn ($record) => blank($record->designer_id))
                    ->form([
                        \Filament\Forms\Components\Select::make('designer_id')
                            ->label('Designer')
                            ->options(fn () => User::query()
                                ->where('role', UserRole::Design->value)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->fillForm(fn ($record) => [
                        'designer_id' => $record->designer_id,
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'designer_id' => $data['designer_id'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title(($data['designer_id'] ?? null) ? 'Designer assigned' : 'Designer unassigned')
                            ->success()
                            ->send();
                    }),
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
                    ->visible(fn ($record) => $record->materialRequests()
                        ->whereColumn('issued_quantity', '<', 'requested_quantity')
                        ->whereDoesntHave('pendingIssueApprovals', fn ($query) => $query->where('status', 'pending'))
                        ->exists())
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
                                        return 'If this exceeds the required quantity, it will be queued for approval instead of issuing immediately.';
                                    })
                            ])->columns(2)
                            ->default(fn () => $record->materialRequests()
                                ->whereColumn('issued_quantity', '<', 'requested_quantity')
                                ->whereDoesntHave('pendingIssueApprovals', fn ($query) => $query->where('status', 'pending'))
                                ->get()
                                ->map(fn ($mr) => [
                                    'material_request_id' => $mr->id,
                                    'inventory_item_id' => $mr->inventory_item_id,
                                    'quantity' => $mr->requested_quantity - $mr->issued_quantity,
                                ])->toArray()),
                    ])
                    ->action(function ($record, $data) {
                        try {
                            $results = ['issued' => 0, 'pending_approval' => 0];

                            foreach ($data['items'] as $item) {
                                if ($item['quantity'] <= 0) continue;

                                $mr = \App\Models\MaterialRequest::findOrFail($item['material_request_id']);
                                $result = app(MaterialIssueService::class)->issue($mr, (int) $data['warehouse_id'], (float) $item['quantity'], auth()->user());
                                $results[$result['status']]++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title(trim(collect([
                                    $results['issued'] ? "{$results['issued']} item(s) issued" : null,
                                    $results['pending_approval'] ? "{$results['pending_approval']} item(s) sent for approval" : null,
                                ])->filter()->implode(' | ')) ?: 'No materials processed')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
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

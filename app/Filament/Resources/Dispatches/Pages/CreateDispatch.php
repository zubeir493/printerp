<?php

namespace App\Filament\Resources\Dispatches\Pages;

use App\Filament\Resources\Dispatches\DispatchResource;
use App\Models\DispatchItem;
use Filament\Resources\Pages\CreateRecord;

class CreateDispatch extends CreateRecord
{
    protected static string $resource = DispatchResource::class;

    /**
     * Temporarily hold quantities from the form so we can create DispatchItem records.
     *
     * @var array<int|string,int>
     */
    protected array $quantities = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->quantities = $data['quantities'] ?? [];

        // Remove quantities so they are not mass-assigned to Dispatch model
        unset($data['quantities']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (empty($this->quantities) || ! $this->record) {
            return;
        }

        foreach ($this->quantities as $taskId => $quantity) {
            $qty = (float) $quantity;

            if ($qty <= 0) {
                continue;
            }

            $this->record->dispatchItems()->create([
                'job_order_task_id' => $taskId,
                'quantity' => $qty,
            ]);

            // Record stock movement (consume WIP)
            $task = \App\Models\JobOrderTask::find($taskId);
            $wipItem = \App\Models\InventoryItem::where('sku', 'WIP-TASK-' . $taskId)->first();
            
            if ($wipItem && $this->record->warehouse_id) {
                \App\Models\StockMovement::create([
                    'inventory_item_id' => $wipItem->id,
                    'warehouse_id' => $this->record->warehouse_id,
                    'type' => 'dispatch',
                    'reference_type' => \App\Models\Dispatch::class,
                    'reference_id' => $this->record->id,
                    'quantity' => -$qty, // Negative for consumption
                    'movement_date' => now(),
                ]);
            }
        }
    }
}

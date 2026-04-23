<?php

namespace App\Observers;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Storage;

class InventoryItemObserver
{
    /**
     * Handle the InventoryItem "updated" event.
     */
    public function updated(InventoryItem $inventoryItem): void
    {
        // Cleanup old image from S3 if it was changed
        if ($inventoryItem->wasChanged('image') && $inventoryItem->getOriginal('image')) {
            Storage::disk('s3')->delete($inventoryItem->getOriginal('image'));
        }
    }

    /**
     * Handle the InventoryItem "deleted" event.
     */
    public function deleted(InventoryItem $inventoryItem): void
    {
        if ($inventoryItem->image) {
            Storage::disk('s3')->delete($inventoryItem->image);
        }
    }
}

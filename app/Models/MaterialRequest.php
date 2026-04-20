<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $fillable = [
        'job_order_task_id',
        'inventory_item_id',
        'required_quantity',
        'requested_quantity',
        'issued_quantity',
    ];

    public function jobOrderTask()
    {
        return $this->belongsTo(JobOrderTask::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
